<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosisStatus extends Model
{
    protected $fillable = [
        'name'
    ];

    public function diagnosis(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }
}
