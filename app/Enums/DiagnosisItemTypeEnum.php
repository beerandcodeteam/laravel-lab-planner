<?php

namespace App\Enums;

enum DiagnosisItemTypeEnum: int
{
    case DoingWell = 1;
    case NeedToImprove = 2;

    public function label(): string
    {
        return match ($this) {
            self::DoingWell => 'DoingWell',
            self::NeedToImprove => 'NeedToImprove',
        };
    }
}
