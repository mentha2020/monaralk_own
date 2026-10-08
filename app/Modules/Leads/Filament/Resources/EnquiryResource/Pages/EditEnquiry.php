<?php

namespace App\Modules\Leads\Filament\Resources\EnquiryResource\Pages;

use App\Modules\Leads\Filament\Resources\EnquiryResource;
use Filament\Resources\Pages\EditRecord;

class EditEnquiry extends EditRecord
{
    protected static string $resource = EnquiryResource::class;
}
