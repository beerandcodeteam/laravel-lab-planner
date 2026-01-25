@props([
    'task',
])

<div
    wire:key="task-{{ $task['id'] }}"
    wire:sort:item="{{ $task['id'] }}"
    class="group"
>
    <x-card wire:sort:handle padding="sm" :hover="true" class="select-none">
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2">
                {{-- Drag Handle --}}
                <div  class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 cursor-grab active:cursor-grabbing">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                    </svg>
                </div>
                <h3 class="font-medium text-neutral-900 dark:text-white text-sm">
                    {{ $task['title'] }}
                </h3>
            </div>
            <div wire:sort:ignore>
                <button class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700 text-neutral-400 transition-all cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between mt-3 ml-6">
            <span @class([
                'text-xs px-2 py-0.5 rounded-full font-medium',
                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $task['type']['name'] === 'habit',
                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $task['type']['name'] === 'single',
            ])>
                {{$task['type']['name']}}
            </span>
            <div class="flex items-center gap-1">
                <div class="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium">
                    U
                </div>
            </div>
        </div>
    </x-card>
</div>
