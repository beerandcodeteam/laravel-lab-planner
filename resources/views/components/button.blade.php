@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    'iconLeft' => null,
    'iconRight' => null,
    'fullWidth' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-dark focus:ring-primary active:bg-primary-darker disabled:hover:bg-primary',
        'secondary' => 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-white hover:bg-neutral-200 dark:hover:bg-neutral-700 focus:ring-neutral-500 active:bg-neutral-300 dark:active:bg-neutral-600 disabled:hover:bg-neutral-100 dark:disabled:hover:bg-neutral-800',
        'tertiary' => 'bg-transparent text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 focus:ring-neutral-500 active:bg-neutral-200 dark:active:bg-neutral-700 disabled:hover:bg-transparent',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 active:bg-red-800 disabled:hover:bg-red-600',
        'outline' => 'bg-transparent border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 focus:ring-neutral-500 active:bg-neutral-100 dark:active:bg-neutral-700 disabled:hover:bg-transparent',
        'ghost' => 'bg-transparent text-primary hover:bg-primary/10 focus:ring-primary active:bg-primary/20 disabled:hover:bg-transparent',
    ];

    $sizes = [
        'xs' => 'text-xs px-2.5 py-1.5',
        'sm' => 'text-sm px-3 py-2',
        'md' => 'text-sm px-4 py-2.5',
        'lg' => 'text-base px-5 py-3',
        'xl' => 'text-base px-6 py-3.5',
    ];

    $iconSizes = [
        'xs' => 'w-3.5 h-3.5',
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-5 h-5',
        'xl' => 'w-6 h-6',
    ];

    $classes = implode(' ', [
        $baseClasses,
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $fullWidth ? 'w-full' : '',
        $loading ? 'pointer-events-none' : '',
    ]);

    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
    $isDisabled = $disabled || $loading;
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href)
        href="{{ $href }}"
    @else
        type="{{ $type }}"
    @endif
    @if($isDisabled && !$href)
        disabled
    @endif
    @if($href && $isDisabled)
        aria-disabled="true"
        class="{{ $classes }} pointer-events-none opacity-50"
    @else
        {{ $attributes->merge(['class' => $classes]) }}
    @endif
>
    {{-- Loading spinner --}}
    <svg class="{{ $iconSize }} hidden in-data-loading:block animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @if($iconLeft)
        <span class="{{ $iconSize }} shrink-0 in-data-loading:hidden">
            {{ $iconLeft }}
        </span>
    @endif

    {{-- Button content --}}
    <span @class(['opacity-0' => $loading && !$iconLeft && !$iconRight, 'sr-only' => $loading && !$slot->isEmpty()])>
        {{ $slot }}
    </span>

    {{-- Right icon --}}
    @if($iconRight && !$loading)
        <span class="{{ $iconSize }} shrink-0">
            {{ $iconRight }}
        </span>
    @endif
</{{ $tag }}>
