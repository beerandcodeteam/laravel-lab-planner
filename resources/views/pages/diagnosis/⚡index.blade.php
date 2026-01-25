<?php

use App\Enums\DiagnosisItemTypeEnum;
use App\Enums\DiagnosisPillarEnum;
use App\Jobs\GeneratePlannerJob;
use App\Models\Diagnosis;
use App\Services\AgentDiagnosisServices;
use App\Services\AgentPlannerServices;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Diagnosis $diagnosis;
    private AgentDiagnosisServices $agentDiagnosisServices;
    private AgentPlannerServices $agentPlannerServices;

    public function boot(AgentDiagnosisServices $agentDiagnosisServices, AgentPlannerServices $agentPlannerServices)
    {
        $this->agentDiagnosisServices = $agentDiagnosisServices;
        $this->agentPlannerServices = $agentPlannerServices;
    }

    public function mount(Diagnosis $diagnosis)
    {
        $this->diagnosis = $diagnosis->load('goal', 'items.type', 'items.pillar');
        abort_if($this->diagnosis->goal->user_id !== auth()->id(), 403);


        if (!$this->diagnosis->description) {
            $this->dispatch('generateDiagnostic');
        }

    }

    public function selectItem($item_id)
    {
        $item = $this->diagnosis->items()->where('id', $item_id)->first();

        $this->diagnosis->items()->where('diagnosis_pillar_id', $item->diagnosis_pillar_id)
            ->update(['user_selected_at' => null]);

        $item->update(['user_selected_at' => now()]);
    }


    #[On('generateDiagnostic')]
    public function generateDiagnostic()
    {
        $response = $this->agentDiagnosisServices->create($this->diagnosis);
        $this->diagnosis->description = $response->structured["diagnosis"];
        $this->diagnosis->save();

        $this->diagnosis->items()
            ->update(['agent_selected_at' => null]);

        $this->diagnosis->items()
            ->whereIn('id', $response->structured['diagnosis_item_ids'])
            ->update(['agent_selected_at' => now()]);


        $this->diagnosis->refresh();

    }

    public function generateActionPlan()
    {

        if (!$this->canCreateActionPlan)
        {
            return;
        }

        auth()->user()->update(['planner_created_at' => now()]);
        dispatch(new GeneratePlannerJob($this->diagnosis));

        $this->redirect(route('kanban.index'), navigate: true);
    }

    #[Computed]
    public function canCreateActionPlan()
    {
        return true;
        $user = auth()->user();

        return is_null($user->planner_created_at) || $user->planner_created_at->diffInWeeks(now()) >= 12;
    }
};
?>

<div class="p-8">
    <div class="flex flex-row items-center justify-between max-w-4xl">
        <div class="flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">
                    {{ $diagnosis->goal->name }}
                </h1>
            </div>
            <p class="text-neutral-600 dark:text-neutral-400 mb-2">
                <span class="font-bold">Meta criada:</span> {{ $diagnosis->goal->created_at->format('d/m/Y H:i') }}
            </p>
            <p class="text-neutral-600 dark:text-neutral-400 mb-8">
                <span class="font-bold">Diagnostico realizado:</span> {{ $diagnosis->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <x-button
                variant="primary"
                class="cursor-pointer"
                wire:click="generateActionPlan"
                :disabled="!$this->canCreateActionPlan"
                wire:loading.attr="disabled"
        >
            <x-slot:iconLeft>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </x-slot:iconLeft>
            Gerar plano de ação
        </x-button>
    </div>

    <section class="mb-12">
        <h2 class="text-2xl font-semibold text-neutral-900 dark:text-white mb-4">Diagnóstico inicial</h2>
        <p class="text-neutral-600 dark:text-neutral-400 mb-6">
            <span wire:loading>
                <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </span>
        </p>

        @if($diagnosis->description)
            <div class="flex flex-col gap-6 mb-8">
                {!! \Illuminate\Support\Str::markdown($diagnosis->description) !!}
            </div>
        @endif


        <x-section size="full" spacingY="xl" background="white">
            <x-section.header title="Domino bem" description="Seus pontos positivos"/>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">Técnico</h3>
                    <div class="flex flex-col mt-4">
                        <ul class="flex flex-col gap-y-4">
                            @foreach($diagnosis->items as $item)
                                @if (
                                    $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::DoingWell
                                    && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Technical
                                )
                                    <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                        <div class="flex items-center justify-between">
                                            <span>{{ $item->description }}</span>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">Estrategico</h3>
                    <div class="flex flex-col mt-4">
                        <ul class="flex flex-col gap-y-4">
                            @foreach($diagnosis->items as $item)
                                @if (
                                    $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::DoingWell
                                    && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Strategic
                                )
                                    <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                        <div class="flex items-center justify-between">
                                            <span>{{ $item->description }}</span>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">Comportamental</h3>
                    <div class="flex flex-col mt-4">
                        <ul class="flex flex-col gap-y-4">
                            @foreach($diagnosis->items as $item)
                                @if (
                                    $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::DoingWell
                                    && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Behavioral
                                )
                                    <li class="shadow-sm hover:shadow-xs p-2 rounded-sm transition-all">
                                        <div class="flex items-center justify-between">
                                            <span>{{ $item->description }}</span>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>

            </div>
        </x-section>

        <x-section class="mt-8" size="full" spacingY="xl" background="white">
            <x-section.header title="Preciso melhorar" description="Seus pontos positivos"/>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">Técnico</h3>
                    <div class="flex flex-col mt-4">
                        <ul class="flex flex-col gap-y-4">
                            @foreach($diagnosis->items as $item)
                                @if (
                                    $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::NeedToImprove
                                    && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Technical
                                )
                                    <li @class([
                                        "shadow-sm hover:shadow-xs p-2 rounded-sm transition-all",
                                        "border-primary shadow-primary border" => $item->agent_selected_at
                                    ])>
                                        <div class="flex items-center justify-between gap-4">
                                            <span>{{ $item->description }}</span>
                                            @if (!$item->user_selected_at)
                                                <x-button wire:click="selectItem({{ $item->id }})"
                                                          class="cursor-pointer" variant="outline" size="sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                         class="size-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="m4.5 12.75 6 6 9-13.5"/>
                                                    </svg>

                                                </x-button>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">Estrategico</h3>
                    <div class="flex flex-col mt-4">
                        <ul class="flex flex-col gap-y-4">
                            @foreach($diagnosis->items as $item)
                                @if (
                                    $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::NeedToImprove
                                    && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Strategic
                                )
                                    <li @class([
                                        "shadow-sm hover:shadow-xs p-2 rounded-sm transition-all",
                                        "border-primary shadow-primary border" => $item->agent_selected_at
                                    ])>
                                        <div class="flex items-center justify-between gap-4">
                                            <span>{{ $item->description }}</span>
                                            @if (!$item->user_selected_at)
                                                <x-button wire:click="selectItem({{ $item->id }})"
                                                          class="cursor-pointer" variant="outline" size="sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                         class="size-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="m4.5 12.75 6 6 9-13.5"/>
                                                    </svg>
                                                </x-button>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">Comportamental</h3>
                    <div class="flex flex-col mt-4">
                        <ul class="flex flex-col gap-y-4">
                            @foreach($diagnosis->items as $item)
                                @if (
                                    $item->diagnosis_item_type_id == DiagnosisItemTypeEnum::NeedToImprove
                                    && $item->diagnosis_pillar_id == DiagnosisPillarEnum::Behavioral
                                )
                                    <li @class([
                                        "shadow-sm hover:shadow-xs p-2 rounded-sm transition-all",
                                        "border-primary shadow-primary border" => $item->agent_selected_at
                                    ])>
                                        <div class="flex items-center justify-between">
                                            <span>{{ $item->description }}</span>
                                            @if (!$item->user_selected_at)
                                                <x-button wire:click="selectItem({{ $item->id }})"
                                                          class="cursor-pointer" variant="outline" size="sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                         class="size-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="m4.5 12.75 6 6 9-13.5"/>
                                                    </svg>
                                                </x-button>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>

            </div>
        </x-section>

    </section>
</div>