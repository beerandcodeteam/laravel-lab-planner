<?php

use App\Models\Goal;
use App\Services\AgentGoalCreationServices;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use \App\Livewire\Forms\GoalForm;

new
#[Title('Home')]
class extends Component {
    private AgentGoalCreationServices $agentServices;
    public bool $showGoalModal = false;
    public GoalForm $form;
    public $goals;

    public function mount()
    {
        $this->loadGoals();
    }

    public function loadGoals()
    {
        $this->goals = Goal::where('user_id', auth()->id())->get();
    }

    public function newGoal()
    {

        $goal = $this->form->store();

        $this->loadGoals();

    }
};
?>

<div class="p-8">
    <div class="max-w-4xl">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">
                Bem-vindo ao {{ config('app.name') }}
            </h1>
            <x-theme-toggle/>
        </div>
        <p class="text-neutral-600 dark:text-neutral-400 mb-8">
            Este é o layout da área logada com menu lateral.
        </p>

        <div class="flex justify-end my-8">
            <x-button
                    wire:click="showGoalModal = true"
                    variant="primary"
                    class="cursor-pointer"
            >
                <x-slot:iconLeft>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </x-slot:iconLeft>
                Nova meta
            </x-button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Product Card -->
            @foreach($goals as $goal)
                <x-card padding="none" :hover="true" :interactive="true">
                    <x-card.body>
                        <h3 class="font-semibold text-neutral-900 dark:text-white mb-2">{{ $goal->name }}</h3>
                        <p class="text-neutral-600 dark:text-neutral-400 text-sm mb-4">
                            {{ $goal->description }}
                        </p>
                        <div class="flex items-center justify-between">
                            <x-button :href="route('goals.index', $goal->id)"  size="sm">Acompanhar</x-button>
                        </div>
                    </x-card.body>
                </x-card>
            @endforeach
        </div>


        <x-modal wire:model="showGoalModal" max-width="lg">
            <x-slot:header>
                <h3>Criar nova meta</h3>
            </x-slot:header>

            <form wire:submit="newGoal" class="flex flex-col gap-y-4">
                <div>
                    <x-form.input
                            label="Nome da meta"
                            placeholder="Digite o nome da sua meta"
                            wire:model="form.name"
                            type="text"
                            name="form.deadline"
                    />
                </div>

                <div>
                    <x-form.input
                            label="Prazo"
                            wire:model="form.deadline"
                            type="date"
                            name="form.deadline"
                    />
                </div>

                <div>
                    <x-form.textarea
                            label="Como você quer ser reconhecido"
                            wire:model="form.description"
                            name="form.description"
                            placeholder="Descreva aqui qual a bandeira e o motivo da escolha"
                    />
                </div>
            </form>

            <x-slot:footer>
                <div class="flex justify-end gap-3">
                    <x-button variant="secondary" wire:click="showGoalModal = false">
                        Cancelar
                    </x-button>
                    <x-button wire:click="newGoal">
                        Confirmar
                    </x-button>
                </div>
            </x-slot:footer>
        </x-modal>


    </div>
</div>
