<?php

use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;

test('the home page renders with hero, featured, latest and brand sections', function () {
    $make = Make::factory()->create(['name' => 'Toyota Lanka']);

    Vehicle::factory()->featured()->create([
        'make_id' => $make->id,
        'trim' => 'HOMESHOWCASE',
        'published_at' => now()->subHour(),
    ]);

    Vehicle::factory()->create([
        'make_id' => $make->id,
        'trim' => 'HOMELATEST',
        'published_at' => now()->subMinutes(5),
    ]);

    Vehicle::factory()->draft()->create([
        'make_id' => $make->id,
        'trim' => 'HOMEDRAFTDOSSIER',
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Featured cars')
        ->assertSee('Latest arrivals')
        ->assertSee('Browse by brand')
        ->assertSee('HOMESHOWCASE')
        ->assertSee('HOMELATEST')
        ->assertDontSee('HOMEDRAFTDOSSIER');

    expect($response->viewData('stats'))->toBeArray()
        ->and($response->viewData('stats')['listings'])->toBe(2)
        ->and($response->viewData('makes')->pluck('name'))->toContain('Toyota Lanka');
});

test('the home page hero posts search parameters to the listing page', function () {
    $make = Make::factory()->create(['name' => 'Nissan Importers']);
    Vehicle::factory()->create(['make_id' => $make->id]);

    $this->get('/')
        ->assertOk()
        ->assertSee(route('vehicles.index'), false)
        ->assertSee('name="keyword"', false)
        ->assertSee('name="make_id"', false)
        ->assertSee('value="'.$make->id.'"', false);
});

test('the home page only lists makes that actually have live cars', function () {
    $withCar = Make::factory()->create(['name' => 'Has Cars']);
    Make::factory()->create(['name' => 'No Cars Yet']);

    Vehicle::factory()->create(['make_id' => $withCar->id]);

    $makes = $this->get('/')->viewData('makes');

    expect($makes->pluck('name')->all())
        ->toContain('Has Cars')
        ->not->toContain('No Cars Yet');
});

test('the home page counts only publicly visible listings', function () {
    Vehicle::factory()->count(2)->create();
    Vehicle::factory()->draft()->create();
    Vehicle::factory()->archived()->create();
    Vehicle::factory()->featured()->create();

    $stats = $this->get('/')->viewData('stats');

    expect($stats['listings'])->toBe(3)
        ->and($stats['featured'])->toBe(1);
});
