# Dockerfile

# Stage 1: Build do frontend
FROM node:20-alpine AS frontend

WORKDIR /app

# Copiar arquivos de dependências do npm
COPY package.json package-lock.json* ./

# Instalar dependências
RUN npm ci

# Copiar código fonte
COPY . .

# Build dos assets
RUN npm run build

# Stage 2: Aplicação PHP
FROM php:8.4-fpm-alpine AS base

# Instalar dependências do sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    libpq-dev

# Instalar extensões PHP
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd opcache

# Configurar opcache para produção
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


# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar arquivos de dependências primeiro (cache layer)
COPY composer.json composer.lock ./

# Instalar dependências (sem dev)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar código da aplicação
COPY . .

# Copiar assets compilados do stage frontend
COPY --from=frontend /app/public/build ./public/build

# Finalizar instalação do composer
RUN composer dump-autoload --optimize

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar configurações do Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copiar configuração do Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]