<?php

use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

function searchCacheResultQueries(array $log): int
{
    return count(array_filter(
        $log,
        fn (array $entry): bool => str_starts_with(ltrim($entry['query']), 'select * from `vehicles`')
    ));
}

beforeEach(function () {
    config(['catalog.cache.enabled' => true]);
});

test('filtered listings are cached until a listing changes', function () {
    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey()]);

    Vehicle::factory()->count(3)->published()->create([
        'make_id' => $make->getKey(),
        'model_id' => $model->getKey(),
    ]);

    $uri = '/vehicles?make_id='.$make->getKey();

    DB::enableQueryLog();

    $this->get($uri)->assertOk();
    $cold = searchCacheResultQueries(DB::getQueryLog());
    DB::flushQueryLog();

    $this->get($uri)->assertOk();
    $warm = searchCacheResultQueries(DB::getQueryLog());
    DB::flushQueryLog();

    $extra = Vehicle::factory()->published()->create([
        'make_id' => $make->getKey(),
        'model_id' => $model->getKey(),
    ]);

    $this->get($uri)->assertOk();
    $afterPublish = searchCacheResultQueries(DB::getQueryLog());

    DB::disableQueryLog();

    expect($cold)->toBe(1)
        ->and($warm)->toBe(0)
        ->and($afterPublish)->toBe(1);

    $this->get($uri)
        ->assertOk()
        ->assertSee(route('vehicles.show', $extra), false);
});

test('the listing cache can be switched off', function () {
    config(['catalog.cache.enabled' => false]);

    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey()]);

    Vehicle::factory()->count(2)->published()->create([
        'make_id' => $make->getKey(),
        'model_id' => $model->getKey(),
    ]);

    $uri = '/vehicles?make_id='.$make->getKey();

    DB::enableQueryLog();

    $this->get($uri)->assertOk();
    $first = searchCacheResultQueries(DB::getQueryLog());
    DB::flushQueryLog();

    $this->get($uri)->assertOk();
    $second = searchCacheResultQueries(DB::getQueryLog());

    DB::disableQueryLog();

    expect($first)->toBe(1)
        ->and($second)->toBe(1);
});

test('a sold vehicle disappears from the cache as soon as its status changes', function () {
    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey()]);

    $vehicle = Vehicle::factory()->published()->create([
        'make_id' => $make->getKey(),
        'model_id' => $model->getKey(),
    ]);

    $uri = '/vehicles?make_id='.$make->getKey();

    $this->get($uri)->assertOk()->assertSee(route('vehicles.show', $vehicle), false);

    $vehicle->update(['is_featured' => true]);

    $this->get($uri)->assertOk()->assertSee(route('vehicles.show', $vehicle), false);

    $vehicle->delete();

    $this->get($uri)->assertOk()->assertDontSee(route('vehicles.show', $vehicle), false);
});
