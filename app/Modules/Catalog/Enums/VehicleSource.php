<?php

namespace App\Modules\Catalog\Enums;

enum VehicleSource: string
{
    case Admin = 'admin';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Added by staff',
            self::Public => 'Public submission',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Public => 'warning',
        };
    }
}
