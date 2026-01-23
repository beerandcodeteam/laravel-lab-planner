# Dockerfile

# Stage 1: Build do frontend
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --production=false

COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./

RUN npm run build

# Stage 2: Dependências PHP
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Stage 3: Aplicação PHP
FROM php:8.4-fpm-alpine AS base

# Instalar dependências - separando runtime de build
RUN apk add --no-cache \
    # Runtime
    nginx \
    supervisor \
    libpng \
    oniguruma \
    libxml2 \
    libpq \
    curl \
    # Build (serão removidas)
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libpq-dev \
    $PHPIZE_DEPS \
    # Instalar extensões
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache \
    # Remover dependências de build
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Configurar PHP para produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configurar opcache otimizado para produção
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=64" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=32531" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_file_override=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/opcache.ini

# Configurações PHP adicionais para performance
RUN echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/performance.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/performance.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/performance.ini \
    && echo "max_execution_time=30" >> /usr/local/etc/php/conf.d/performance.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/performance.ini

# Configurar PHP-FPM para performance
RUN sed -i 's/pm = dynamic/pm = static/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/pm.max_children = 5/pm.max_children = 20/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;pm.max_requests = 500/pm.max_requests = 1000/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# Copiar dependências do composer
COPY --from=composer /app/vendor ./vendor

# Copiar código da aplicação
COPY --chown=www-data:www-data . .

# Copiar assets compilados
COPY --from=frontend /app/public/build ./public/build

# Finalizar composer e otimizar Laravel
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Permissões
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copiar configs
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Remover arquivos desnecessários
RUN rm -rf \
    .git \
    .github \
    tests \
    docker \
    node_modules \
    .env.example \
    phpunit.xml \
    README.md \
    .editorconfig \
    .gitignore \
    .gitattributes

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]