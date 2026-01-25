<?php

namespace App\Traits;

use App\Enums\TaskStepEnum;
use App\Models\LessonEmbedding;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Distance;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Facades\Tool;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use function view;

trait HasAgentTools
{
    public function technicalDeepDive()
    {
        return Tool::as('Technical_Deep_Dive')
            ->for('Constroi um plano de ação para desenvolvimento de habilidades tecnicas')
            ->withStringParameter('goal', 'A descrição da meta do usuario')
            ->withStringParameter('deadline', 'A data final esperada do usuario')
            ->withStringParameter('technical_focus', 'Qual o principal problema tecnico que o usuario ira focar')
            ->using(function (string $goal, string $deadline, string $technical_focus) {

                return Prism::text()
                    ->using(Provider::OpenAI, 'gpt-4.1-mini')
                    ->withSystemPrompt(view('prompts.technical-deep-dive'))
                    ->withPrompt(view('prompts.execution-technical-deep-dive', [
                        'goal' => $goal,
                        'deadline' => $deadline,
                        'technicalFocus' => $technical_focus,
                    ]))
                    ->withMaxSteps(5)
                    ->withClientOptions(['timeout' => 120])
                    ->withTools([$this->searchKnowledgeBase()])
                    ->asText()->text;

            });

    }

    public function strategyAndPlaning()
    {
        return Tool::as('Strategy_E_Planning')
            ->for('Constroi um plano de ação para desenvolvimento de habilidades estrategicas')
            ->withStringParameter('goal', 'A descrição da meta do usuario')
            ->withStringParameter('deadline', 'A data final esperada do usuario')
            ->withStringParameter('strategic_focus', 'Qual o principal problema tecnico que o usuario ira focar')
            ->using(function (string $goal, string $deadline, string $strategic_focus) {

                return Prism::text()
                    ->using(Provider::OpenAI, 'gpt-4.1-mini')
                    ->withSystemPrompt(view('prompts.strategy-and-planning', ['goal' => $goal, 'strategy_focus' => $strategic_focus]))
                    ->withPrompt(view('prompts.execution-strategy-and-planning', [
                        'goal' => $goal,
                        'strategy_focus' => $strategic_focus,
                    ]))
                    ->withMaxSteps(5)
                    ->withClientOptions(['timeout' => 120])
                    ->withTools([$this->searchKnowledgeBase()])
                    ->asText()->text;

            });

    }

    public function behavioralAndSoftSkills()
    {
        return Tool::as('Behavioral_E_SoftSkills')
            ->for('Constroi um plano de ação para desenvolvimento de habilidades comportamentais')
            ->withStringParameter('goal', 'A descrição da meta do usuario')
            ->withStringParameter('deadline', 'A data final esperada do usuario')
            ->withStringParameter('behavioral_focus', 'Qual o principal problema tecnico que o usuario ira focar')
            ->using(function (string $goal, string $deadline, string $behavioral_focus) {

                return Prism::text()
                    ->using(Provider::OpenAI, 'gpt-4.1-mini')
                    ->withSystemPrompt(view('prompts.technical-deep-dive'))
                    ->withPrompt(view('prompts.execution-behavioral-and-soft-skills', [
                        'goal' => $goal,
                        'behavioral_focus' => $behavioral_focus,
                    ]))
                    ->withMaxSteps(5)
                    ->withClientOptions(['timeout' => 120])
                    ->withTools([$this->searchKnowledgeBase()])
                    ->asText()->text;

            });

    }

    public function searchKnowledgeBase()
    {
        return Tool::as('search_knowledge_base')
            ->for('Faz busca na base de conhecimento repleta de aulas tecnicas, estrategicas e comportamentais')
            ->withStringParameter('query', 'Query para busca em base de embeddings com L2 Distance')
            ->using(function (string $query) {

                Log::info('QUERY: '.$query);

                $response = Prism::embeddings()
                    ->using(Provider::OpenAI, 'text-embedding-3-small')
                    ->fromInput($query)
                    ->asEmbeddings();

                $embeddings = $response->embeddings[0]->embedding;

                return LessonEmbedding::query()->nearestNeighbors('embedding', $embeddings, Distance::L2)->take(15)->get()->toJson();
            });
    }

    public function storeTasks(int $goalId): \Prism\Prism\Tool
    {
        return Tool::as('store_tasks')
            ->for('Armazena um array de tarefas no banco de dados para uma meta específica')
            ->withArrayParameter('tasks', 'Array de tarefas contendo title, task_type_id e week_prevision', new ObjectSchema(
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
            ->using(function (array $tasks) use ($goalId) {

                Log::info('tasks count: '.count($tasks));

                $createdTasks = [];

                foreach ($tasks as $index => $taskData) {
                    $createdTasks[] = Task::create([
                        'goal_id' => $goalId,
                        'title' => $taskData['title'],
                        'task_step_id' => TaskStepEnum::Backlog,
                        'task_type_id' => $taskData['task_type_id'],
                        'week_prevision' => $taskData['week_prevision'],
                        'order' => $index + 1,
                    ]);
                }

                return 'Tarefas criadas com sucesso: '.count($createdTasks);
            });
    }
}
