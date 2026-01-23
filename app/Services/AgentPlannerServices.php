<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\Goal;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class AgentPlannerServices
{

    public function diagnosisOutputSchema(): ObjectSchema
    {
       return new ObjectSchema(
           name: 'questions',
           description: 'List of questions',
           properties: [
               new StringSchema('diagnosis', 'O diagnostico completo em texto corrido formato em markdown'),
               new ArraySchema(
                   name: 'diagnosis_item_ids',
                   description: 'Ids de 3 diagnosticos marcados para serem trabalhados',
                   items: new StringSchema(
                       name: 'diagnosis_item_id',
                       description: 'O Id numerico do item que precisa ser melhorado',
                   )
               )
           ],
           requiredFields: ['diagnosis', 'diagnosis_item_ids']
       );
    }

    public function create(Diagnosis $diagnosis)
    {

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-5-mini')
            ->withSchema($this->diagnosisOutputSchema())
            ->withSystemPrompt(view('prompts.diagnosis-system-prompt'))
            ->withPrompt($diagnosis->load('goal', 'items.type', 'items.pillar')->toJson())
            ->withClientOptions(['timeout' => 120])
            ->asStructured();

        return $response;
    }

}