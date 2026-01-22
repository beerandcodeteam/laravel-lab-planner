<?php

namespace App\Models;

use App\Enums\DiagnosisItemTypeEnum;
use App\Enums\DiagnosisPillarEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosisItem extends Model
{
    protected $fillable = [
        'diagnosis_id',
        'diagnosis_item_type_id',
        'diagnosis_pillar_id',
        'description',
        'agent_selected_at',
        'user_selected_at'
    ];

    protected function casts(): array
    {
        return [
            'diagnosis_item_type_id' => DiagnosisItemTypeEnum::class,
            'diagnosis_pillar_id' => DiagnosisPillarEnum::class,
        ];
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DiagnosisItemType::class);
    }

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(DiagnosisPillar::class);
    }
}
