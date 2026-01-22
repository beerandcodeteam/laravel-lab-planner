<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalQuestion extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'goal_id',
        'question',
        'answer',
        'order',
    ];

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
