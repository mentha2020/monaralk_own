<?php

namespace App\Modules\Catalog\Enums;

enum VehicleCondition: string
{
    case New = 'new';
    case CertifiedPreOwned = 'certified_pre_owned';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::CertifiedPreOwned => 'Certified Pre-Owned',
            self::Used => 'Used',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'success',
            self::CertifiedPreOwned => 'info',
            self::Used => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
