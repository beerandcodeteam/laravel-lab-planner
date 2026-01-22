<?php

namespace App\Livewire\Forms;

use App\Enums\DiagnosisItemTypeEnum;
use App\Enums\DiagnosisPillarEnum;
use App\Models\Diagnosis;
use App\Models\DiagnosisItem;
use App\Models\Goal;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DiagnosisForm extends Form
{
    public array $input;

    public function rules()
    {

        return [
            'input.Technical.DoingWell' => 'nullable|string|min:3|max:255',
            'input.Strategic.DoingWell' => 'nullable|string|min:3|max:255',
            'input.Behavioral.DoingWell' => 'nullable|string|min:3|max:255',
            'input.Technical.NeedToImprove' => 'nullable|string|min:3|max:255',
            'input.Strategic.NeedToImprove' => 'nullable|string|min:3|max:255',
            'input.Behavioral.NeedToImprove' => 'nullable|string|min:3|max:255',
        ];

    }

    public function createDiagnosis(Goal $goal)
    {
        return $goal->diagnoses()->create();
    }

    public function addItem(string $pillar, string $type, Diagnosis $diagnosis)
    {
        $this->validate();

        $diagnosis->items()->create([
            'diagnosis_pillar_id' => constant(DiagnosisPillarEnum::class . '::' . $pillar)->value,
            'diagnosis_item_type_id' => constant(DiagnosisItemTypeEnum::class . '::' . $type)->value,
            'description' => $this->input[$pillar][$type],
        ]);

        unset($this->input[$pillar][$type]);
    }

    public function removeItem($item_id)
    {
        DiagnosisItem::find($item_id)->delete();
    }
}
