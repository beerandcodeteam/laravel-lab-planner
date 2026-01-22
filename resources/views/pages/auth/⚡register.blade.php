<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Livewire\Forms\RegisterForm;

new
#[Layout('layouts.auth')]
#[Title('Criar Conta')]
class extends Component {
    public RegisterForm $form;

    public function register(): void
    {

        $this->form->register();

        session()->regenerate();

        $this->redirect(route('home'),
            navigate: true);

    }
};
?>

<div>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-neutral-900 dark:text-white mb-2">
            Criar sua conta
        </h1>
        <p class="text-neutral-600 dark:text-neutral-400">
            Preencha os dados abaixo para começar.
        </p>
    </div>

    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="mb-6">
            <x-alert variant="success" :dismissible="true">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    {{-- Register Form --}}
    <form wire:submit="register" class="space-y-5">
        {{-- Name --}}
        <div>
            <x-form.label for="name" required>Nome completo</x-form.label>
            <x-form.input
                id="name"
                type="text"
                name="form.name"
                wire:model="form.name"
                placeholder="João Silva"
                required
                autofocus
            />
            <x-form.error name="form.name"/>
        </div>

        {{-- Email --}}
        <div>
            <x-form.label for="email" required>Email</x-form.label>
            <x-form.input
                id="email"
                type="email"
                name="form.email"
                wire:model="form.email"
                placeholder="seu@email.com"
                required
            />
            <x-form.error name="form.email"/>
        </div>

        {{-- Password --}}
        <div>
            <x-form.label for="password" required>Senha</x-form.label>
            <x-form.input
                id="password"
                type="password"
                name="form.password"
                wire:model="form.password"
                placeholder="••••••••"
                required
            />
            <x-form.hint>Mínimo de 8 caracteres</x-form.hint>
            <x-form.error name="form.password"/>
        </div>

        {{-- Password Confirmation --}}
        <div>
            <x-form.label for="password_confirmation" required>Confirmar senha</x-form.label>
            <x-form.input
                id="password_confirmation"
                type="password"
                name="form.password_confirmation"
                wire:model="form.password_confirmation"
                placeholder="••••••••"
                required
            />
            <x-form.error name="form.password_confirmation"/>
        </div>

        {{-- Terms --}}
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <x-form.checkbox
                    id="terms"
                    wire:model="terms"
                    required
                />
            </div>
            <div class="ml-2">
                <x-form.label for="terms" class="mb-0">
                    <span class="text-sm text-neutral-600 dark:text-neutral-400">
                        Eu concordo com os
                        <a href="/terms" class="text-primary hover:underline">Termos de Serviço</a>
                        e
                        <a href="/privacy" class="text-primary hover:underline">Política de Privacidade</a>
                    </span>
                </x-form.label>
                <x-form.error name="terms"/>
            </div>
        </div>

        {{-- Submit Button --}}
        <div>
            <x-button type="submit" variant="primary" size="lg" fullWidth>
                Criar conta
            </x-button>
        </div>
    </form>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-neutral-300 dark:border-neutral-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-neutral-50 dark:bg-neutral-1100 text-neutral-500 dark:text-neutral-400">
                Ou cadastre-se com
            </span>
        </div>
    </div>

    {{-- Social Register --}}
    <div class="grid grid-cols-2 gap-3">
        <x-button variant="outline" fullWidth>
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    fill="#4285F4"/>
                <path
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    fill="#34A853"/>
                <path
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                    fill="#FBBC05"/>
                <path
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                    fill="#EA4335"/>
            </svg>
            Google
        </x-button>

        <x-button variant="outline" fullWidth>
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
            </svg>
            GitHub
        </x-button>
    </div>

    {{-- Sign In Link --}}
    <div class="mt-8 text-center">
        <p class="text-sm text-neutral-600 dark:text-neutral-400">
            Já tem uma conta?
            <a href="/login" class="font-medium text-primary hover:underline" wire:navigate>
                Entrar
            </a>
        </p>
    </div>
</div>
