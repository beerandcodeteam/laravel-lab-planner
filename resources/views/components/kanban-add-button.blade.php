@props([
    'columnKey',
])

<button
    wire:click="openNewTaskModal('{{ $columnKey }}')"
    wire:sort:ignore
    class="flex items-center justify-center gap-2 p-3 rounded-lg border-2 border-dashed border-neutral-300 dark:border-neutral-600 text-neutral-500 dark:text-neutral-400 hover:border-neutral-400 dark:hover:border-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors cursor-pointer"
>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
    <span class="text-sm">Adicionar tarefa</span>
</button>
