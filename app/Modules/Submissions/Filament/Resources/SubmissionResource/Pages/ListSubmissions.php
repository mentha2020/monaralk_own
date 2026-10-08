<?php

namespace App\Modules\Submissions\Filament\Resources\SubmissionResource\Pages;

use App\Modules\Submissions\Filament\Resources\SubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;
}
