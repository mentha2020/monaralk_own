<?php

namespace App\Modules\Catalog\Enums;

enum VehicleStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Sold = 'sold';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending Review',
            self::Published => 'Published',
            self::Sold => 'Sold',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
            self::Sold => 'info',
            self::Archived => 'danger',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this, [self::Published, self::Sold], true);
    }
}
