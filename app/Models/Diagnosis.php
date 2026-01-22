<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnosis extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'diagnosis_status_id',
        'goal_id',
        'description',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(DiagnosisStatus::class, 'diagnosis_status_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DiagnosisItem::class);
    }
}
