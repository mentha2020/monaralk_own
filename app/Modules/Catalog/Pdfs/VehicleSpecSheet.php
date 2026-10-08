<?php

namespace App\Modules\Catalog\Pdfs;

use App\Modules\Catalog\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class VehicleSpecSheet
{
    public function download(Vehicle $vehicle): Response
    {
        $vehicle->load([
            'make',
            'model',
            'bodyType',
            'fuelType',
            'transmission',
            'exteriorColor',
            'interiorColor',
            'features',
            'images',
        ]);

        return Pdf::loadView('pdf.vehicle-spec', [
            'vehicle' => $vehicle,
        ])->download($vehicle->slug.'-spec-sheet.pdf');
    }
}
