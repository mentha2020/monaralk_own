<?php

namespace App\Modules\Settings\Filament\Resources\SettingResource\Pages;

use App\Modules\Settings\Filament\Resources\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;
}
