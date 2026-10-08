<?php

namespace App\Modules\Catalog\Filament\Resources\FuelTypeResource\Pages;

use App\Modules\Catalog\Filament\Resources\FuelTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFuelType extends EditRecord
{
    protected static string $resource = FuelTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
