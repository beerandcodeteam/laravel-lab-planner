@props([
    'name' => null,
    'show' => false,
    'maxWidth' => 'md',
    'closeable' => true,
    'closeOnClickOutside' => true,
    'closeOnEscape' => true,
])

@php
    $maxWidthClasses = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        'full' => 'sm:max-w-full sm:m-4',
    ][$maxWidth] ?? 'sm:max-w-md';
@endphp

<div
    @if($attributes->wire('model')->value())
        x-data="{ show: @entangle($attributes->wire('model')) }"
    @else
        x-data="{ show: {{ $show ? 'true' : 'false' }} }"
    @endif
    @if($name)
        x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
        x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    @endif
    @if($closeOnEscape && $closeable)
        x-on:keydown.escape.window="show = false"
    @endif
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @if($closeOnClickOutside && $closeable)
            @click="show = false"
        @endif
        class="fixed inset-0 bg-overlay-dark dark:bg-overlay-darker transition-opacity"
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            {{-- Modal Panel --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.stop
                {{ $attributes->except('wire:model')->merge(['class' => "relative w-full $maxWidthClasses bg-white dark:bg-neutral-900 rounded-xl shadow-xl transform transition-all text-left overflow-hidden"]) }}
            >
                {{-- Close Button --}}
                @if($closeable)
                    <button
                        type="button"
                        @click="show = false"
                        class="absolute top-4 right-4 p-1 rounded-lg text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors z-10"
                    >
                        <span class="sr-only">Fechar</span>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif

                {{-- Header --}}
                @if(isset($header))
                    <div class="px-6 pt-6 pb-4">
                        {{ $header }}
                    </div>
                @endif

                {{-- Body --}}
                <div @class(['px-6', 'pt-6' => !isset($header), 'pb-6'])>
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                @if(isset($footer))
                    <div class="px-6 py-4 bg-neutral-50 dark:bg-neutral-950 border-t border-neutral-200 dark:border-neutral-800">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
