<?php

namespace App\Models;

use App\Enums\GoalSituationEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Goal extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'goal_situation_id',
        'user_id',
        'name',
        'deadline',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal_situation_id' => GoalSituationEnum::class,
            'deadline' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<GoalSituation, $this>
     */
    public function situation(): BelongsTo
    {
        return $this->belongsTo(GoalSituation::class, 'goal_situation_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<GoalQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(GoalQuestion::class);
    }

    /**
     * @return HasOne<GoalDiagnosis, $this>
     */
    public function diagnoses(): hasMany
    {
        return $this->hasMany(Diagnosis::class);
    }
}
