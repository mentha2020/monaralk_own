<?php

namespace App\Modules\Catalog\Filament\Resources\TransmissionResource\Pages;

use App\Modules\Catalog\Filament\Resources\TransmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmissions extends ListRecords
{
    protected static string $resource = TransmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
