<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="$store.darkMode.on && 'dark'">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Login' }} - {{ config('app.name') }}</title>

        {{-- Prevent flash of wrong theme --}}
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-neutral-50 dark:bg-neutral-1100 antialiased">
        <div class="min-h-screen flex">
            {{-- Left Side - Visual/Branding --}}
            <div class="hidden lg:flex lg:w-1/2 bg-primary relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-primary to-primary-700 dark:from-primary-600 dark:to-primary-900"></div>

                {{-- Decorative circles --}}
                <div class="absolute top-0 left-0 w-96 h-96 bg-white/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/10 rounded-full translate-x-1/2 translate-y-1/2"></div>

                <div class="relative z-10 flex flex-col justify-between p-12 text-white">
                    {{-- Logo/Brand --}}
                    <div>
                        <a href="/" class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-2xl font-bold">{{ config('app.name') }}</span>
                        </a>
                    </div>

                    {{-- Content --}}
                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold leading-tight">
                            Bem-vindo de volta!
                        </h1>
                        <p class="text-lg text-white/80 max-w-md">
                            Acesse sua conta e continue evoluindo em sua carreira.
                        </p>

                        <div class="flex gap-4 pt-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm">Rápido e Seguro</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm">Acesso 24/7</span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="text-sm text-white/60">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                    </div>
                </div>
            </div>

            {{-- Right Side - Form --}}
            <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
                <div class="w-full max-w-md">
                    {{-- Theme Toggle --}}
                    <div class="flex justify-end mb-8">
                        <x-theme-toggle />
                    </div>

                    {{-- Mobile Logo --}}
                    <div class="lg:hidden mb-8 text-center">
                        <a href="/" class="inline-flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-neutral-900 dark:text-white">{{ config('app.name') }}</span>
                        </a>
                    </div>

                    {{-- Main Content --}}
                    <div>
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Alpine.js Dark Mode Store --}}
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('darkMode', {
                    on: localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
                    toggle() {
                        this.on = !this.on;
                        localStorage.theme = this.on ? 'dark' : 'light';
                    }
                });
            });
        </script>

        @livewireScripts
    </body>
</html>
