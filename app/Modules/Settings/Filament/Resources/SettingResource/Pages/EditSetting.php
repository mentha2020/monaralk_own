<?php

namespace App\Modules\Settings\Filament\Resources\SettingResource\Pages;

use App\Modules\Settings\Filament\Resources\SettingResource;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;
}
