<?php

namespace App\Services;

use App\Enums\DiagnosisPillarEnum;
use App\Enums\TaskStepEnum;
use App\Models\Diagnosis;
use App\Models\Goal;
use App\Models\TaskStep;
use App\Models\TaskType;
use App\Traits\HasAgentTools;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Psy\Util\Str;
use function view;

class AgentPlannerServices
{

    public $teste = "ola eu sou o teste";

    use HasAgentTools;

    public function planOutputSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'plan',
            description: 'Lista de tarefas para o plano de ação',
            properties: [
                new ArraySchema(
                    name: 'tasks',
                    description: 'Lista de Tarefas para o plano de ação',
                    items: new ObjectSchema(
                        name: 'task',
                        description: 'Estrutura da tarefa para o plano de acao',
                        properties: [
                            new StringSchema(name: 'title', description: 'Titulo da tarefa para o plano de acao'),
                            new StringSchema(name: 'task_type_id', description: 'O ID numérico correspondente (Hábito ou Tarefa Única)'),
                            new StringSchema(name: 'week_prevision', description: 'Previsão de qual semana é melhor de aplicar a tarefa')
                        ],
                        // ADICIONE 'week_prevision' AQUI:
                        requiredFields: ['title', 'task_type_id', 'week_prevision']
                    )
                )
            ],
            // Verifique se os campos abaixo também batem com as properties do objeto raiz,
            // se não existirem no nível superior, remova-os ou adicione-os às properties.
            requiredFields: ['tasks']
        );
    }

    public function create(Diagnosis $diagnosis)
    {

        $response = Prism::structured()
            ->withSchema($this->planOutputSchema())
            ->using(Provider::OpenAI, 'gpt-5-mini')
            ->withSystemPrompt(view('prompts.beer-and-code-mentor', ['task_type' => TaskType::all()]))
            ->withPrompt(view('prompts.execution-beer-and-code-mentor', [
                'meta' => $diagnosis->goal->name,
                'deadline' => $diagnosis->goal->deadline,
                'description' => $diagnosis->goal->description,
                'technical_focus' => $diagnosis->items
                    ->where('diagnosis_pillar_id', DiagnosisPillarEnum::Technical)
                    ->whereNotNull('user_selected_at')->first()->description,
                'strategic_focus' => $diagnosis->items
                    ->where('diagnosis_pillar_id', DiagnosisPillarEnum::Strategic)
                    ->whereNotNull('user_selected_at')->first()->description,
                'behavioral_focus' => $diagnosis->items
                    ->where('diagnosis_pillar_id', DiagnosisPillarEnum::Behavioral)
                    ->whereNotNull('user_selected_at')->first()->description,
                'situation' => $diagnosis->description
            ]))
            ->withMaxSteps(5)
            ->withClientOptions(['timeout' => 120])
            ->withTools([
                $this->technicalDeepDive(),
                $this->strategyAndPlaning(),
                $this->behavioralAndSoftSkills(),
                $this->storeTasks($diagnosis->goal->id)
            ])
            ->asStructured();

        return $response;
    }

}