<?php

use App\Enums\TaskStepEnum;
use App\Models\Task;
use App\Models\TaskStep;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;

new
#[Title('Kanban')]
class extends Component {
    public bool $showTaskModal = false;
    public ?string $editingColumn = null;
    public string $newTaskTitle = '';
    public string $newTaskDescription = '';

    #[Computed]
    public function steps(): \Illuminate\Database\Eloquent\Collection
    {
        return TaskStep::with([
            'tasks' => fn ($q) => $q->forCurrentUser()->orderBy('order'),
            'tasks.goal',
            'tasks.type',
        ])->get();
    }

    public function moveTaskBacklog($taskId, $newPosition)
    {
        $this->moveTask($taskId, $newPosition, TaskStepEnum::Backlog->value);
    }

    public function moveTaskToDo($taskId, $newPosition)
    {
        $this->moveTask($taskId, $newPosition, TaskStepEnum::ToDo->value);
    }

    public function moveTaskDoing($taskId, $newPosition)
    {
        $this->moveTask($taskId, $newPosition, TaskStepEnum::Doing->value);
    }

    public function moveTaskDone($taskId, $newPosition)
    {
        $this->moveTask($taskId, $newPosition, TaskStepEnum::Done->value);
    }

    public function moveTask(int $taskId, int $newPosition, int $targetStepId): void
    {
        $task = Task::findOrFail($taskId);
        $sourceStepId = $task->task_step_id;
        $oldPosition = $task->order;

        if ($sourceStepId === $targetStepId) {
            if ($oldPosition === $newPosition) {
                return;
            }

            if ($oldPosition < $newPosition) {
                Task::query()
                    ->forCurrentUser()
                    ->where('task_step_id', $targetStepId)
                    ->where('order', '>', $oldPosition)
                    ->where('order', '<=', $newPosition)
                    ->decrement('order');
            } else {
                // Moving up: shift items between new and old position down
                Task::query()
                    ->forCurrentUser()
                    ->where('task_step_id', $targetStepId)
                    ->where('order', '>=', $newPosition)
                    ->where('order', '<', $oldPosition)
                    ->increment('order');
            }

            $task->update(['order' => $newPosition]);
        } else {
            // Moving to a different step

            // Close the gap in the source step
            Task::query()
                ->forCurrentUser()
                ->where('task_step_id', $sourceStepId)
                ->where('order', '>', $oldPosition)
                ->decrement('order');

            // Make room in the target step
            Task::query()
                ->forCurrentUser()
                ->where('task_step_id', $targetStepId)
                ->where('order', '>=', $newPosition)
                ->increment('order');

            // Move the task to the new step and position
            $task->update([
                'task_step_id' => $targetStepId,
                'order' => $newPosition,
            ]);
        }
    }

    public function newTask()
    {
        dump('iu');
    }


};
?>

<div class="p-8 h-full">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">
                Kanban
            </h1>
            <p class="text-neutral-600 dark:text-neutral-400 mt-1">
                Gerencie suas tarefas de forma visual
            </p>
        </div>
        <x-theme-toggle/>
    </div>


    <div
            class="flex gap-6 overflow-x-auto pb-4"
    >

        @foreach($this->steps as $step)
            <div class="flex-shrink-0 w-80" wire:key="{{$step['name']}}">

                <x-kanban-column-header
                        :title="$step['name']"
                        :color="$step['color']"
                        :count="count($step['tasks'])"
                        :column-key="$step['name']"
                        wire:click="newTask"
                />

                <div
                        class="flex flex-col gap-3 min-h-96 p-2 rounded-lg bg-neutral-100 dark:bg-neutral-800/50"
                        wire:sort="moveTask{{str_replace("-", "", $step['name'])}}"
                        wire:sort:group="kanban"
                >
                    @forelse($step['tasks'] as $task)
                        <x-kanban-card
                                :task="$task"
                        />

                    @empty
                        <x-kanban-empty-state/>
                    @endforelse

                </div>


            </div>
        @endforeach


    </div>


</div>
