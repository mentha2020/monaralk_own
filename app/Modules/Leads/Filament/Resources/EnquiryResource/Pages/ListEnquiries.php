<?php

namespace App\Modules\Leads\Filament\Resources\EnquiryResource\Pages;

use App\Modules\Leads\Filament\Resources\EnquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;
}
