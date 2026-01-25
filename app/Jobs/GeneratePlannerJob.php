<?php

namespace App\Jobs;

use App\Enums\TaskStepEnum;
use App\Models\Diagnosis;
use App\Services\AgentPlannerServices;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GeneratePlannerJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Diagnosis $diagnosis)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AgentPlannerServices $agentPlannerServices): void
    {
        Log::info("iniciando plano {$this->diagnosis->id}");
        $response = $agentPlannerServices->create($this->diagnosis);

        $count = 0;
        foreach ($response->structured['tasks'] as $task) {
            $this->diagnosis->goal->tasks()->create([
                ...$task,
                'task_step_id' => TaskStepEnum::Backlog,
                'order' => $count++
            ]);
        }

        Log::info("finalizado plano {$this->diagnosis->id}");

    }
}
