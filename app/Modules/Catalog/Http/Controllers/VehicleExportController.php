<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Exports\VehicleExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class VehicleExportController
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isStaff()) {
            abort(403);
        }

        return Excel::download(
            new VehicleExport,
            'monaralk-inventory-'.now()->format('Y-m-d').'.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
