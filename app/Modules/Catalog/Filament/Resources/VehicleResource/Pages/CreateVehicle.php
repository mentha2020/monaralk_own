<?php

namespace App\Modules\Catalog\Filament\Resources\VehicleResource\Pages;

use App\Modules\Catalog\Enums\VehicleSource;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Filament\Resources\VehicleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['source'] = VehicleSource::Admin->value;
        $data['views_count'] = 0;

        if (($data['status'] ?? null) === VehicleStatus::Published->value && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
