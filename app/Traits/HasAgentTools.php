<?php

namespace App\Traits;


use App\Models\LessonEmbedding;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Distance;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Facades\Tool;
use function dd;
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
                        'technicalFocus' => $technical_focus
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
                        'strategy_focus' => $strategic_focus
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
                        'behavioral_focus' => $behavioral_focus
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

                Log::info('QUERY: ' . $query);

                $response = Prism::embeddings()
                    ->using(Provider::OpenAI, 'text-embedding-3-small')
                    ->fromInput($query)
                    ->asEmbeddings();

                $embeddings = $response->embeddings[0]->embedding;

                return LessonEmbedding::query()->nearestNeighbors('embedding', $embeddings, Distance::L2)->take(15)->get()->toJson();
            });
    }
}
