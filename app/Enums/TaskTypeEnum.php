<?php

namespace App\Enums;

enum TaskTypeEnum: int
{
    case Single = 1;
    case Habit = 2;

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Habit => 'Habit',
        };
    }
}
