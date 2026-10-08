<?php

namespace App\Modules\Accounts\Filament\Resources\UserResource\Pages;

use App\Modules\Accounts\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
