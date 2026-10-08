<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use Illuminate\View\View;

class HomeController
{
    public function __invoke(): View
    {
        $eager = ['make', 'model', 'fuelType', 'transmission', 'coverImage'];

        $featured = Vehicle::query()->featured()->with($eager)->latest('published_at')->take(4)->get();

        $latest = Vehicle::query()->publiclyVisible()->with($eager)->latest('published_at')->take(8)->get();

        $makes = Make::query()
            ->where('is_active', true)
            ->withCount(['vehicles as listings_count' => fn ($query) => $query->publiclyVisible()])
            ->orderBy('name')
            ->get()
            ->filter(fn (Make $make): bool => $make->listings_count > 0)
            ->values();

        $stats = [
            'listings' => Vehicle::query()->publiclyVisible()->count(),
            'makes' => $makes->count(),
            'featured' => Vehicle::query()->featured()->count(),
        ];

        return view('home', compact('featured', 'latest', 'makes', 'stats'));
    }
}
