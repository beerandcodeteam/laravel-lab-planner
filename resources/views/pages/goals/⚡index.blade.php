<?php

use App\Enums\DiagnosisItemTypeEnum;
use App\Enums\DiagnosisPillarEnum;
use App\Livewire\Forms\DiagnosisForm;
use App\Models\Diagnosis;
use App\Models\DiagnosisPillar;
use App\Models\Goal;
use Livewire\Component;

new class extends Component {
    public DiagnosisForm $form;
    public Goal $goal;
    public bool $showDiagnosisModal;
    public ?Diagnosis $diagnosis;


    public function mount(Goal $goal)
    {
        abort_if(!$goal || $goal->user_id !== auth()->id(), 403);

        $this->goal = $goal;

        $this->diagnosis = $this->goal->diagnoses->where('diagnosis_status_id', 1)->first();
        if (!$this->diagnosis) {
            $this->diagnosis = $this->form->createDiagnosis($this->goal);
        }

        $this->loadDiagnosisItem();
    }

    public function loadDiagnosisItem()
    {
        $this->goal->load('diagnoses.items.type', 'diagnoses.items.pillar');
    }

    public function newDiagnosis()
    {
        $this->showDiagnosisModal = true;
    }

    public function addItem($pillar, $type)
    {
        $this->form->addItem($pillar, $type, $this->diagnosis);
    }

    public function removeItem($item_id)
    {
        $this->form->removeItem($item_id);
    }

    public function confirmDiagnosis()
    {

        $this->diagnosis->diagnosis_status_id = 2;
        $this->diagnosis->save();
        $this->redirect(route('diagnosis.index', $this->diagnosis->id),
            navigate: true
        );
        
    }
};
?>

<div class="p-8">
    <div class="flex flex-row items-center justify-between max-w-4xl">
        <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">
                    {{ $goal->name }}
                </h1>
            </div>
            <p class="text-neutral-600 dark:text-neutral-400 mb-8">
                {{ $goal->description }}
            </p>
        </div>

        <x-button
                variant="primary"
                class="cursor-pointer"
                wire:click="newDiagnosis"
        >
            <x-slot:iconLeft>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </x-slot:iconLeft>
            Novo diagnostico
        </x-button>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Product Card -->
        @foreach($goal->diagnoses as $diagnosis)
            <x-card padding="none" :hover="true" :interactive="true">
                <x-card.body>
                    <h3 class="font-semibold text-neutral-900 dark:text-white mb-2">{{ $diagnosis->created_at->format('d/m/Y') }}</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-sm mb-4">
                        {{ $diagnosis->status->name }}
                    </p>
                    <div class="flex items-center justify-between">
                        @if ($diagnosis->diagnosis_status_id == 1)
                            <x-button class="cursor-pointer" wire:click="newDiagnosis"  size="sm">Completar</x-button>
                        @else
                            <x-button :href="route('diagnosis.index', $diagnosis->id)"  size="sm">Acompanhar</x-button>
                        @endif
                    </div>
                </x-card.body>
            </x-card>
        @endforeach
    </div>

    <x-modal wire:model="showDiagnosisModal" max-width="5xl">
        <x-slot:header>
            <h3>Criar novo Diagnostico</h3>
        </x-slot:header>

        <h2 class="text-xl mb-4">Domino Bem</h2>
        <div
                class="grid grid-cols-1 md:grid-cols-3 gap-6"
        >

            <x-card>
                <h3 class="font-bold text-lg">Pilar Tecnico</h3>

                <div class="flex items-start gap-y-6 gap-x-2 mt-2">
                    <x-form.input
                            name="form.input.{{DiagnosisPillarEnum::Technical->label()}}.{{DiagnosisItemTypeEnum::DoingWell->label()}}"
                            placeholder="O que voce manja tecnicamente"
                            wire:model="form.input.{{DiagnosisPillarEnum::Technical->label()}}.{{DiagnosisItemTypeEnum::DoingWell->label()}}"
                    ></x-form.input>
                    <x-button
                            wire:click="addItem('{{ DiagnosisPillarEnum::Technical->label() }}', '{{ DiagnosisItemTypeEnum::DoingWell->label() }}')">
                        Add
                    </x-button>
                </div>

                <div class="flex flex-col mt-4">
                    <ul class="flex flex-col gap-y-4">
                        @foreach($this->diagnosis->items as $item)
                            @if (
                                $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::DoingWell
                                && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Technical
                            )
                                <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $item->description }}</span>
                                        <x-button wire:click="removeItem({{ $item->id }})" class="cursor-pointer" variant="outline" size="sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </x-button>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </x-card>

            <x-card>
                <h3 class="font-bold text-lg">Pilar Estrategico</h3>
                <div class="flex items-start gap-y-6 gap-x-2 mt-2">
                    <x-form.input
                            placeholder="O que voce manja tecnicamente"
                            wire:model="form.input.{{DiagnosisPillarEnum::Strategic->label()}}.{{DiagnosisItemTypeEnum::DoingWell->label()}}"
                            name="form.input.{{DiagnosisPillarEnum::Strategic->label()}}.{{DiagnosisItemTypeEnum::DoingWell->label()}}"
                    ></x-form.input>
                    <x-button
                            wire:click="addItem('{{ DiagnosisPillarEnum::Strategic->label() }}', '{{ DiagnosisItemTypeEnum::DoingWell->label() }}')">
                        Add
                    </x-button>
                </div>

                <div class="flex flex-col mt-4">
                    <ul class="flex flex-col gap-y-4">
                        @foreach($this->diagnosis->items as $item)
                            @if (
                                $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::DoingWell
                                && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Strategic
                            )
                                <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $item->description }}</span>
                                        <x-button wire:click="removeItem({{ $item->id }})" class="cursor-pointer" variant="outline" size="sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </x-button>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </x-card>

            <x-card>
                <h3 class="font-bold text-lg">Pilar Comportamental</h3>
                <div class="flex items-start gap-y-6 gap-x-2 mt-2">
                    <x-form.input
                            placeholder="O que voce manja tecnicamente"
                            wire:model="form.input.{{DiagnosisPillarEnum::Behavioral->label()}}.{{DiagnosisItemTypeEnum::DoingWell->label()}}"
                            name="form.input.{{DiagnosisPillarEnum::Behavioral->label()}}.{{DiagnosisItemTypeEnum::DoingWell->label()}}"
                    ></x-form.input>
                    <x-button
                            wire:click="addItem('{{ DiagnosisPillarEnum::Behavioral->label() }}', '{{ DiagnosisItemTypeEnum::DoingWell->label() }}')">
                        Add
                    </x-button>
                </div>

                <div class="flex flex-col mt-4">
                    <ul class="flex flex-col gap-y-4">
                        @foreach($this->diagnosis->items as $item)
                            @if (
                                $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::DoingWell
                                && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Behavioral
                            )
                                <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $item->description }}</span>
                                        <x-button wire:click="removeItem({{ $item->id }})" class="cursor-pointer" variant="outline" size="sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </x-button>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </x-card>

        </div>

        <h2 class="text-xl mt-8 mb-4">Preciso melhorar</h2>
        <div
                class="grid grid-cols-1 md:grid-cols-3 gap-6"
        >

            <x-card>
                <h3 class="font-bold text-lg">Pilar Tecnico</h3>

                <div class="flex items-start gap-y-6 gap-x-2 mt-2">
                    <x-form.input
                            placeholder="O que voce manja tecnicamente"
                            wire:model="form.input.{{DiagnosisPillarEnum::Technical->label()}}.{{DiagnosisItemTypeEnum::NeedToImprove->label()}}"
                            name="form.input.{{DiagnosisPillarEnum::Technical->label()}}.{{DiagnosisItemTypeEnum::NeedToImprove->label()}}"
                    ></x-form.input>
                    <x-button
                            wire:click="addItem('{{ DiagnosisPillarEnum::Technical->label() }}', '{{ DiagnosisItemTypeEnum::NeedToImprove->label() }}')">
                        Add
                    </x-button>
                </div>

                <div class="flex flex-col mt-4">
                    <ul class="flex flex-col gap-y-4">
                        @foreach($this->diagnosis->items as $item)
                            @if (
                                $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::NeedToImprove
                                && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Technical
                            )
                                <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $item->description }}</span>
                                        <x-button wire:click="removeItem({{ $item->id }})" class="cursor-pointer" variant="outline" size="sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </x-button>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </x-card>

            <x-card>
                <h3 class="font-bold text-lg">Pilar Estrategico</h3>
                <div class="flex items-start gap-y-6 gap-x-2 mt-2">
                    <x-form.input
                            placeholder="O que voce manja tecnicamente"
                            wire:model="form.input.{{DiagnosisPillarEnum::Strategic->label()}}.{{DiagnosisItemTypeEnum::NeedToImprove->label()}}"
                            name="form.input.{{DiagnosisPillarEnum::Strategic->label()}}.{{DiagnosisItemTypeEnum::NeedToImprove->label()}}"
                    ></x-form.input>
                    <x-button
                            wire:click="addItem('{{ DiagnosisPillarEnum::Strategic->label() }}', '{{ DiagnosisItemTypeEnum::NeedToImprove->label() }}')">
                        Add
                    </x-button>
                </div>

                <div class="flex flex-col mt-4">
                    <ul class="flex flex-col gap-y-4">
                        @foreach($this->diagnosis->items as $item)
                            @if (
                                $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::NeedToImprove
                                && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Strategic
                            )
                                <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $item->description }}</span>
                                        <x-button wire:click="removeItem({{ $item->id }})" class="cursor-pointer" variant="outline" size="sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </x-button>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </x-card>

            <x-card>
                <h3 class="font-bold text-lg">Pilar Comportamental</h3>
                <div class="flex items-start gap-y-6 gap-x-2 mt-2">
                    <x-form.input
                            placeholder="O que voce manja tecnicamente"
                            wire:model="form.input.{{DiagnosisPillarEnum::Behavioral->label()}}.{{DiagnosisItemTypeEnum::NeedToImprove->label()}}"
                            name="form.input.{{DiagnosisPillarEnum::Behavioral->label()}}.{{DiagnosisItemTypeEnum::NeedToImprove->label()}}"
                    ></x-form.input>
                    <x-button
                            wire:click="addItem('{{ DiagnosisPillarEnum::Behavioral->label() }}', '{{ DiagnosisItemTypeEnum::NeedToImprove->label() }}')">
                        Add
                    </x-button>
                </div>

                <div class="flex flex-col mt-4">
                    <ul class="flex flex-col gap-y-4">
                        @foreach($this->diagnosis->items as $item)
                            @if (
                                $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::NeedToImprove
                                && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Behavioral
                            )
                                <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $item->description }}</span>
                                        <x-button wire:click="removeItem({{ $item->id }})" class="cursor-pointer" variant="outline" size="sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </x-button>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </x-card>

        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button variant="secondary" wire:click="showGoalModal = false">
                    Cancelar
                </x-button>
                <x-button wire:click="confirmDiagnosis">
                    Confirmar
                </x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>