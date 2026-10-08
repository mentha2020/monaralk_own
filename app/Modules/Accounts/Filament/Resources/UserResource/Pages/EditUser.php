<?php

namespace App\Modules\Accounts\Filament\Resources\UserResource\Pages;

use App\Modules\Accounts\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
