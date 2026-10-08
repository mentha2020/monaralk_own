<?php

use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Feature;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;

function publishedVehicle(array $attributes = []): Vehicle
{
    return Vehicle::factory()->published()->create($attributes);
}

test('published scope only returns published vehicles with a past published_at', function () {
    publishedVehicle();
    Vehicle::factory()->draft()->create();
    Vehicle::factory()->pending()->create();
    Vehicle::factory()->archived()->create();
    Vehicle::factory()->create(['status' => VehicleStatus::Published, 'published_at' => now()->addDay()]);

    expect(Vehicle::published()->count())->toBe(1);
});

test('publicly visible includes sold vehicles but not drafts', function () {
    publishedVehicle();
    Vehicle::factory()->sold()->create(['published_at' => now()->subDay()]);
    Vehicle::factory()->draft()->create();
    Vehicle::factory()->pending()->create();
    Vehicle::factory()->archived()->create();

    expect(Vehicle::publiclyVisible()->count())->toBe(2);
});

test('featured scope returns only publicly visible featured vehicles', function () {
    publishedVehicle(['is_featured' => true]);
    publishedVehicle(['is_featured' => false]);
    Vehicle::factory()->draft()->create(['is_featured' => true]);

    $featured = Vehicle::featured()->get();

    expect($featured)->toHaveCount(1)
        ->and($featured->first()->is_featured)->toBeTrue();
});

test('filter hides vehicles that are not publicly visible', function () {
    publishedVehicle();
    Vehicle::factory()->draft()->create();

    expect(Vehicle::filter([])->count())->toBe(1);
});

test('filter by make model and taxonomy columns', function () {
    $make = Make::factory()->create(['name' => 'Zebra Motors']);
    $model = VehicleModel::factory()->create(['make_id' => $make->id, 'name' => 'Zebra One']);
    $body = BodyType::create(['name' => 'TestBody', 'slug' => 'testbody']);

    $match = publishedVehicle([
        'make_id' => $make->id,
        'model_id' => $model->id,
        'body_type_id' => $body->id,
        'condition' => 'new',
    ]);

    publishedVehicle(['condition' => 'used']);

    expect(Vehicle::filter(['make_id' => $make->id])->pluck('id')->all())->toBe([$match->id])
        ->and(Vehicle::filter(['model_id' => $model->id])->count())->toBe(1)
        ->and(Vehicle::filter(['body_type_id' => $body->id])->count())->toBe(1)
        ->and(Vehicle::filter(['condition' => 'new'])->count())->toBe(1);
});

test('filter by price year and mileage ranges', function () {
    $cheap = publishedVehicle(['price' => 3_000_000, 'year' => 2015, 'mileage_km' => 120_000]);
    $mid = publishedVehicle(['price' => 9_000_000, 'year' => 2020, 'mileage_km' => 60_000]);
    publishedVehicle(['price' => 30_000_000, 'year' => 2024, 'mileage_km' => 5_000]);

    expect(Vehicle::filter(['min_price' => 2_000_000, 'max_price' => 10_000_000])->pluck('id')->all())
        ->toContain($cheap->id, $mid->id)
        ->and(Vehicle::filter(['min_price' => 2_000_000, 'max_price' => 10_000_000])->count())->toBe(2);

    expect(Vehicle::filter(['min_year' => 2018, 'max_year' => 2021])->count())->toBe(1)
        ->and(Vehicle::filter(['max_mileage' => 70_000])->count())->toBe(2);
});

test('keyword filter matches make model trim and description', function () {
    $byTrim = publishedVehicle(['trim' => 'PhantomTrim', 'description' => 'ordinary text']);
    $byDescription = publishedVehicle(['trim' => 'GX', 'description' => 'a unicorn sighting inside the cabin']);

    $make = Make::factory()->create(['name' => 'Rainbow Automotive']);
    $model = VehicleModel::factory()->create(['make_id' => $make->id, 'name' => 'Sunbeam']);
    $byMake = publishedVehicle(['make_id' => $make->id, 'model_id' => $model->id]);

    publishedVehicle(['trim' => 'EX', 'description' => 'nothing unusual here']);

    expect(Vehicle::filter(['keyword' => 'PhantomTrim'])->pluck('id')->all())->toBe([$byTrim->id])
        ->and(Vehicle::filter(['keyword' => 'unicorn'])->pluck('id')->all())->toBe([$byDescription->id])
        ->and(Vehicle::filter(['keyword' => 'Rainbow'])->pluck('id')->all())->toContain($byMake->id)
        ->and(Vehicle::filter(['keyword' => 'Sunbeam'])->pluck('id')->all())->toContain($byMake->id)
        ->and(Vehicle::filter(['keyword' => ''])->count())->toBe(4);
});

test('filter sorts by price and publication date', function () {
    publishedVehicle(['price' => 5_000_000, 'published_at' => now()->subDays(3)]);
    publishedVehicle(['price' => 1_000_000, 'published_at' => now()->subDays(2)]);
    publishedVehicle(['price' => 9_000_000, 'published_at' => now()->subDays(1)]);

    expect(Vehicle::filter(['sort' => 'price_asc'])->pluck('price')->map(fn ($p) => (float) $p)->all())
        ->toBe([1_000_000.0, 5_000_000.0, 9_000_000.0]);

    expect(Vehicle::filter(['sort' => 'price_desc'])->pluck('price')->map(fn ($p) => (float) $p)->all())
        ->toBe([9_000_000.0, 5_000_000.0, 1_000_000.0]);

    expect(Vehicle::filter([])->pluck('published_at')->first()->toDateString())
        ->toBe(now()->subDays(1)->toDateString());
});

test('filter eager loads the relations used by listing cards', function () {
    publishedVehicle();

    $vehicles = Vehicle::filter([])->get();

    expect($vehicles->first()->relationLoaded('make'))->toBeTrue()
        ->and($vehicles->first()->relationLoaded('model'))->toBeTrue()
        ->and($vehicles->first()->relationLoaded('coverImage'))->toBeTrue();
});

test('filter by featured flag', function () {
    publishedVehicle(['is_featured' => true]);
    publishedVehicle(['is_featured' => false]);

    expect(Vehicle::filter(['featured' => true])->count())->toBe(1)
        ->and(Vehicle::filter(['featured' => '1'])->count())->toBe(1);
});

test('a vehicle keeps a single row when syncing the same feature twice', function () {
    $vehicle = publishedVehicle();
    $feature = Feature::factory()->create();

    $vehicle->features()->syncWithoutDetaching([$feature->id]);
    $vehicle->features()->syncWithoutDetaching([$feature->id]);

    expect($vehicle->features()->count())->toBe(1);
});
