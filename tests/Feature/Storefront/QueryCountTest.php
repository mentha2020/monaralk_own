<?php

use App\Models\User;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

test('the storefront does not run one query per listing row', function () {
    $user = User::factory()->create();
    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->id]);
    $bodyType = BodyType::factory()->create();
    $fuelType = FuelType::factory()->create();
    $transmission = Transmission::factory()->create();
    $color = Color::factory()->create();

    foreach (range(1, 20) as $index) {
        $vehicle = Vehicle::factory()->create([
            'user_id' => $user->id,
            'make_id' => $make->id,
            'model_id' => $model->id,
            'body_type_id' => $bodyType->id,
            'fuel_type_id' => $fuelType->id,
            'transmission_id' => $transmission->id,
            'exterior_color_id' => $color->id,
            'interior_color_id' => $color->id,
            'published_at' => now()->subMinutes($index),
        ]);

        VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->id]);
    }

    DB::enableQueryLog();

    $this->get('/')->assertOk();
    $homeQueries = count(DB::getQueryLog());

    DB::flushQueryLog();

    $this->get(route('vehicles.index'))->assertOk();
    $listingQueries = count(DB::getQueryLog());

    DB::flushQueryLog();

    $vehicle = Vehicle::first();
    DB::flushQueryLog();

    $this->get(route('vehicles.show', $vehicle))->assertOk();
    $detailQueries = count(DB::getQueryLog());

    DB::disableQueryLog();

    expect($homeQueries)->toBeLessThan(20)
        ->and($listingQueries)->toBeLessThan(20)
        ->and($detailQueries)->toBeLessThan(20);
});
