<?php

namespace App\Modules\Catalog\Filament\Resources\VehicleModelResource\Pages;

use App\Modules\Catalog\Filament\Resources\VehicleModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleModel extends EditRecord
{
    protected static string $resource = VehicleModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
