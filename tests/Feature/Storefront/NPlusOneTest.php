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

test('listing pages run the same queries no matter how many rows render', function () {
    $user = User::factory()->create();
    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey()]);
    $bodyType = BodyType::factory()->create();
    $fuelType = FuelType::factory()->create();
    $transmission = Transmission::factory()->create();
    $color = Color::factory()->create();

    $build = function (int $count) use ($user, $make, $model, $bodyType, $fuelType, $transmission, $color): void {
        Vehicle::query()->delete();

        foreach (range(1, $count) as $index) {
            $vehicle = Vehicle::factory()->published()->featured()->create([
                'user_id' => $user->getKey(),
                'make_id' => $make->getKey(),
                'model_id' => $model->getKey(),
                'body_type_id' => $bodyType->getKey(),
                'fuel_type_id' => $fuelType->getKey(),
                'transmission_id' => $transmission->getKey(),
                'exterior_color_id' => $color->getKey(),
                'interior_color_id' => $color->getKey(),
                'published_at' => now()->subMinutes($index),
            ]);

            VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->getKey()]);
        }
    };

    $measure = function (): array {
        $counts = [];

        foreach (['/', '/vehicles'] as $uri) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->get($uri)->assertOk();

            $counts[$uri] = count(DB::getQueryLog());
        }

        DB::disableQueryLog();

        return $counts;
    };

    $this->get('/')->assertOk();
    $this->get('/vehicles')->assertOk();

    $build(6);
    $small = $measure();

    $build(20);
    $large = $measure();

    expect($large['/'])->toBe($small['/'])
        ->and($large['/vehicles'])->toBe($small['/vehicles']);
});

test('paging the listing keeps the query count and serves later rows on page two', function () {
    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey()]);

    foreach (range(1, 30) as $index) {
        Vehicle::factory()->published()->create([
            'make_id' => $make->getKey(),
            'model_id' => $model->getKey(),
            'published_at' => now()->subMinutes($index),
        ]);
    }

    $ordered = Vehicle::query()->orderByDesc('published_at')->get();
    $uri = '/vehicles?make_id='.$make->getKey();

    $this->get($uri)->assertOk();

    DB::enableQueryLog();

    $pageOne = $this->get($uri)->assertOk()->getContent();
    $pageOneCount = count(DB::getQueryLog());
    DB::flushQueryLog();

    $pageTwo = $this->get($uri.'&page=2')->assertOk()->getContent();
    $pageTwoCount = count(DB::getQueryLog());

    DB::disableQueryLog();

    expect($pageOneCount)->toBe($pageTwoCount)
        ->and($pageOne)->toContain(route('vehicles.show', $ordered[0]))
        ->and($pageOne)->not->toContain(route('vehicles.show', $ordered[12]))
        ->and($pageTwo)->toContain(route('vehicles.show', $ordered[12]));
});
