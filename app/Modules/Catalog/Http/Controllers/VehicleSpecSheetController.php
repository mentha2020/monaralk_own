<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Pdfs\VehicleSpecSheet;

class VehicleSpecSheetController
{
    public function __invoke(Vehicle $vehicle, VehicleSpecSheet $sheet)
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isStaff()) {
            abort(403);
        }

        return $sheet->download($vehicle);
    }
}
