<?php

namespace App\Modules\Catalog\Filament\Resources\BodyTypeResource\Pages;

use App\Modules\Catalog\Filament\Resources\BodyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBodyType extends EditRecord
{
    protected static string $resource = BodyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
