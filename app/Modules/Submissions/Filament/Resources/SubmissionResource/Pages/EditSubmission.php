<?php

namespace App\Modules\Submissions\Filament\Resources\SubmissionResource\Pages;

use App\Modules\Submissions\Filament\Resources\SubmissionResource;
use Filament\Resources\Pages\EditRecord;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;
}
