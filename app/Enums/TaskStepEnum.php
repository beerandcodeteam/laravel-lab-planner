<?php

namespace App\Enums;

enum TaskStepEnum: int
{
    case Backlog = 1;
    case ToDo = 2;
    case Doing = 3;
    case Done = 4;

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::ToDo => 'To-Do',
            self::Doing => 'Doing',
            self::Done => 'Done',
        };
    }
}
