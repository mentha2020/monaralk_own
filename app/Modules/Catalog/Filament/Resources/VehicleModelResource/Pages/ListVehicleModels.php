<?php

namespace App\Modules\Catalog\Filament\Resources\VehicleModelResource\Pages;

use App\Modules\Catalog\Filament\Resources\VehicleModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleModels extends ListRecords
{
    protected static string $resource = VehicleModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
