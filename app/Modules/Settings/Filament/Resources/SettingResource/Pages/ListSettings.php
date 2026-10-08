<?php

namespace App\Modules\Settings\Filament\Resources\SettingResource\Pages;

use App\Modules\Settings\Filament\Resources\SettingResource;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;
}
