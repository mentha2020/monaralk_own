<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Accounts\Services\SavedVehicles;
use App\Modules\Catalog\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedVehicleController
{
    public function __construct(private readonly SavedVehicles $saved) {}

    public function favourites(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $vehicles = $user->savedVehicles()
            ->where('type', SavedType::Favourite)
            ->with(['vehicle.make', 'vehicle.model', 'vehicle.fuelType', 'vehicle.transmission', 'vehicle.coverImage'])
            ->latest('saved_vehicles.id')
            ->get()
            ->pluck('vehicle')
            ->filter()
            ->values();

        return view('accounts.favourites', [
            'vehicles' => $vehicles,
            'counts' => $this->saved->counts((int) $user->getKey()),
        ]);
    }

    public function compare(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $vehicles = $user->savedVehicles()
            ->where('type', SavedType::Compare)
            ->with(['vehicle.make', 'vehicle.model', 'vehicle.fuelType', 'vehicle.transmission', 'vehicle.bodyType', 'vehicle.exteriorColor', 'vehicle.coverImage'])
            ->orderBy('saved_vehicles.id')
            ->limit(SavedVehicles::COMPARE_CAP)
            ->get()
            ->pluck('vehicle')
            ->filter()
            ->values();

        return view('accounts.compare', [
            'vehicles' => $vehicles,
            'cap' => SavedVehicles::COMPARE_CAP,
            'counts' => $this->saved->counts((int) $user->getKey()),
        ]);
    }

    public function destroy(Request $request, Vehicle $vehicle, string $type): RedirectResponse
    {
        $savedType = SavedType::tryFrom($type) ?? abort(404);

        SavedVehicle::query()
            ->where('user_id', $request->user()?->getKey())
            ->where('vehicle_id', $vehicle->getKey())
            ->where('type', $savedType)
            ->delete();

        return back()->with('status', __('Removed from your :list list.', ['list' => strtolower($savedType->label())]));
    }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'favourite' => ['sometimes', 'array', 'max:100'],
            'favourite.*' => ['integer', 'exists:vehicles,id'],
            'compare' => ['sometimes', 'array', 'max:50'],
            'compare.*' => ['integer', 'exists:vehicles,id'],
        ]);

        $this->saved->merge(
            (int) $request->user()->getKey(),
            $data['favourite'] ?? [],
            $data['compare'] ?? [],
        );

        return response()->json([
            'ok' => true,
            'counts' => $this->saved->counts((int) $request->user()->getKey()),
        ]);
    }
}
