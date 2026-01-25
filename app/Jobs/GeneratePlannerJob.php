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
        $agentPlannerServices->create($this->diagnosis);
        Log::info("finalizado plano {$this->diagnosis->id}");

    }
}
