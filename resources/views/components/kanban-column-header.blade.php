@props([
    'title',
    'color' => 'neutral',
    'count' => 0,
    'columnKey',
])

<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
        <span @class([
            'w-3 h-3 rounded-full',
            'bg-neutral-400' => $color === 'neutral',
            'bg-blue-500' => $color === 'blue',
            'bg-yellow-500' => $color === 'yellow',
            'bg-green-500' => $color === 'green',
        ])></span>
        <h2 class="font-semibold text-neutral-900 dark:text-white">
            {{ $title }}
        </h2>
        <span class="text-sm text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded-full">
            {{ $count }}
        </span>
    </div>
    <button
            {{ $attributes->only('wire:click') }}
        class="p-1 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>
</div>
