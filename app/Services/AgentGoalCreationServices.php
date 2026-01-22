<?php

namespace App\Services;

use App\Models\Goal;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class AgentGoalCreationServices
{

    public function questionsOutputSchema(): ObjectSchema
    {
       return new ObjectSchema(
           name: 'questions',
           description: 'List of questions',
           properties: [
               new ArraySchema(
                   name: 'questions_output',
                   description: 'Questions output structure',
                   items: new StringSchema(
                       name: 'question',
                       description: 'O enunciado da questão de aprofundamento na meta',
                   )
               )
           ],
           requiredFields: ['questions_output']
       );
    }

    public function create(Goal $goal)
    {

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-5-mini')
            ->withSchema($this->questionsOutputSchema())
            ->withPrompt(view('prompts.goal-creation-prompt', ['goal' => $goal]))
            ->asStructured();


        dd($response);

    }

}