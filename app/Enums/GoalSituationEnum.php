<?php

namespace App\Enums;

enum GoalSituationEnum: int
{
    case InProgress = 1;
    case Achieved = 2;
    case Abandoned = 3;

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Achieved => 'Achieved',
            self::Abandoned => 'Abandoned',
        };
    }
}
