<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Rule;
use App\Livewire\Forms\LoginForm;

new
#[Layout('layouts.auth')]
#[Title('Login')]
class extends Component
{

    public LoginForm $form;

    public function login()
    {
        $this->form->authenticate();

        session()->regenerate();

        return $this->redirect(
            session('url.intended', route('home')),
            navigate: true
        );
    }

};
?>

<div>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-neutral-900 dark:text-white mb-2">
            Entrar na sua conta
        </h1>
        <p class="text-neutral-600 dark:text-neutral-400">
            Bem-vindo de volta! Por favor, entre com suas credenciais.
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

    {{-- Login Form --}}
    <form wire:submit.prevent="login" class="space-y-6">
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
                autofocus
            />
        </div>


        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <x-form.label for="password" required>Senha</x-form.label>
                <a href="/forgot-password" class="text-sm text-primary hover:underline">
                    Esqueceu a senha?
                </a>
            </div>
            <x-form.input
                id="password"
                type="password"
                name="form.password"
                wire:model="form.password"
                placeholder="••••••••"
                required
            />
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center">
            <x-form.checkbox
                id="remember"
                wire:model="form.remember"
            />
            <x-form.label for="remember" class="ml-2 mb-0">
                Lembrar de mim
            </x-form.label>
        </div>

        {{-- Submit Button --}}
        <div>
            <x-button type="submit" variant="primary" size="lg" fullWidth>
                Entrar
            </x-button>
        </div>
    </form>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-neutral-300 dark:border-neutral-700"></div>
        </div>

    </div>

    {{-- Sign Up Link --}}
    <div class="mt-8 text-center">
        <p class="text-sm text-neutral-600 dark:text-neutral-400">
            Não tem uma conta?
            <a href="/register" class="font-medium text-primary hover:underline" wire:navigate>
                Criar conta
            </a>
        </p>
    </div>
</div>
