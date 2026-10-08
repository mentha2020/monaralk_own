<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Pdfs\VehicleSpecSheet;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VehicleController
{
    public function index(): View
    {
        return view('vehicles.index');
    }

    public function show(Request $request, Vehicle $vehicle): View
    {
        abort_unless($this->isVisible($vehicle), 404);

        $vehicle->load([
            'make', 'model', 'bodyType', 'fuelType', 'transmission',
            'exteriorColor', 'interiorColor', 'images', 'features',
        ]);

        $related = Vehicle::query()
            ->publiclyVisible()
            ->whereKeyNot($vehicle->getKey())
            ->where('make_id', $vehicle->make_id)
            ->with(['make', 'model', 'fuelType', 'transmission', 'coverImage'])
            ->latest('published_at')
            ->take(4)
            ->get();

        $viewKey = 'vehicle.viewed.'.$vehicle->getKey();

        if (! $request->session()->has($viewKey)) {
            $vehicle->incrementViews();
            $request->session()->put($viewKey, true);
        }

        return view('vehicles.show', [
            'vehicle' => $vehicle,
            'related' => $related,
            'gallery' => $vehicle->images->values(),
        ]);
    }

    public function specSheet(Vehicle $vehicle, VehicleSpecSheet $sheet): Response
    {
        abort_unless($this->isVisible($vehicle), 404);

        return $sheet->download($vehicle);
    }

    private function isVisible(Vehicle $vehicle): bool
    {
        return $vehicle->status->isPubliclyVisible()
            && $vehicle->published_at !== null
            && $vehicle->published_at->lte(now());
    }
}
