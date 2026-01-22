@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'size' => 'md',
    'iconLeft' => null,
    'iconRight' => null,
    'prefix' => null,
    'suffix' => null,
    'bag' => 'default',
])

@php
    $id = $id ?? $name;
    $hasError = $name && $errors->{$bag}->has($name);

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
    ][$size] ?? 'px-4 py-2.5 text-sm';

    $iconSizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-5 h-5',
    ][$size] ?? 'w-5 h-5';

    $baseClasses = 'block w-full rounded-lg border bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 dark:placeholder-neutral-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0';

    $stateClasses = match(true) {
        $disabled => 'border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 cursor-not-allowed opacity-60',
        $hasError => 'border-red-500 dark:border-red-400 focus:border-red-500 focus:ring-red-500/20',
        default => 'border-neutral-300 dark:border-neutral-600 focus:border-primary focus:ring-primary/20 hover:border-neutral-400 dark:hover:border-neutral-500',
    };

    $inputClasses = $baseClasses . ' ' . $sizeClasses . ' ' . $stateClasses;

    if ($iconLeft || $prefix) {
        $inputClasses .= ' pl-10';
    }
    if ($iconRight || $suffix) {
        $inputClasses .= ' pr-10';
    }
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
        <x-form.label :for="$id" :required="$required" class="mb-1.5">
            {{ $label }}
        </x-form.label>
    @endif

    <div class="relative">
        {{-- Left Icon or Prefix --}}
        @if($iconLeft)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-neutral-400 dark:text-neutral-500">
                <x-dynamic-component :component="$iconLeft" :class="$iconSizeClasses" />
            </div>
        @elseif($prefix)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-neutral-500 dark:text-neutral-400 text-sm">
                {{ $prefix }}
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" @endif
            @if($id) id="{{ $id }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($required) required @endif
            {{ $attributes->except(['class', 'type'])->merge(['class' => $inputClasses]) }}
        />

        {{-- Right Icon or Suffix --}}
        @if($iconRight)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-neutral-400 dark:text-neutral-500">
                <x-dynamic-component :component="$iconRight" :class="$iconSizeClasses" />
            </div>
        @elseif($suffix)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-neutral-500 dark:text-neutral-400 text-sm">
                {{ $suffix }}
            </div>
        @endif
    </div>

</div>
