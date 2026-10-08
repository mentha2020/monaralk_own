<?php

namespace App\Modules\Accounts\Enums;

enum SavedType: string
{
    case Favourite = 'favourite';
    case Compare = 'compare';

    public function label(): string
    {
        return match ($this) {
            self::Favourite => 'Favourite',
            self::Compare => 'Compare',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Favourite => 'danger',
            self::Compare => 'info',
        };
    }
}
