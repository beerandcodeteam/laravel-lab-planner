<?php

namespace App\Enums;

enum DiagnosisPillarEnum: int
{
    case Technical = 1;
    case Strategic = 2;
    case Behavioral = 3;

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical',
            self::Strategic => 'Strategic',
            self::Behavioral => 'Behavioral',
        };
    }
}
