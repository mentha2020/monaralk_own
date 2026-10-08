<?php

namespace App\Modules\Accounts\Filament\Resources\UserResource\Pages;

use App\Modules\Accounts\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
