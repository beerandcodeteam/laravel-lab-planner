<?php

namespace App\Livewire\Forms;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginForm extends Form
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;


    public function authenticate()
    {

        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

    }


    protected function ensureIsNotRateLimited(): void
    {
        if (! \RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = \RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);

    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->email).'|'.request()->ip()
        );
    }
}
