<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalSituation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'icon',
        'color',
    ];

    /**
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
