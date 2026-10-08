<?php

use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\Feature;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;

test('the detail page renders a published vehicle', function () {
    $vehicle = Vehicle::factory()->create([
        'trim' => 'DETAILSHOWCASE',
        'description' => 'A carefully kept example with full service history.',
        'views_count' => 987654,
    ]);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('DETAILSHOWCASE')
        ->assertSee($vehicle->formattedPrice)
        ->assertSee('A carefully kept example with full service history.')
        ->assertSee('Finance calculator')
        ->assertSee('Download spec sheet')
        ->assertSee('987,655');
});

test('the detail page hides vehicles that are not publicly visible', function (string $state) {
    $vehicle = Vehicle::factory()->{$state}()->create();

    $this->get(route('vehicles.show', $vehicle))->assertNotFound();
})->with(['draft', 'pending', 'archived']);

test('a scheduled vehicle that is not live yet returns 404', function () {
    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('vehicles.show', $vehicle))->assertNotFound();
});

test('a sold vehicle stays publicly visible', function () {
    $vehicle = Vehicle::factory()->sold()->create(['trim' => 'SOLDCAR']);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('SOLDCAR')
        ->assertSee('Sold');
});

test('an unknown slug returns 404', function () {
    $this->get('/vehicles/no-such-vehicle')->assertNotFound();
});

test('the detail page shows features and related cars from the same make', function () {
    $make = Make::factory()->create();

    $vehicle = Vehicle::factory()->create(['make_id' => $make->id, 'trim' => 'HEROCAR']);
    $sibling = Vehicle::factory()->create(['make_id' => $make->id, 'trim' => 'SIBLINGCAR']);
    Vehicle::factory()->create(['trim' => 'UNRELATEDCAR']);

    $feature = Feature::factory()->create(['name' => 'SUNROOFEXTRA']);
    $vehicle->features()->attach($feature->id);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('SUNROOFEXTRA')
        ->assertSee('Similar cars')
        ->assertSee('SIBLINGCAR')
        ->assertDontSee('UNRELATEDCAR');
});

test('the detail page renders the photo gallery', function () {
    $vehicle = Vehicle::factory()->create();

    VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->id, 'sort_order' => 1]);
    VehicleImage::factory()->create(['vehicle_id' => $vehicle->id, 'sort_order' => 2]);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('storage/vehicles/')
        ->assertSee('View full screen', false)
        ->assertSee('aria-label="Photo 2"', false);
});

test('the detail page counts one view per visitor session', function () {
    $vehicle = Vehicle::factory()->create(['views_count' => 100]);

    $this->get(route('vehicles.show', $vehicle))->assertOk();
    expect($vehicle->fresh()->views_count)->toBe(101);

    $this->get(route('vehicles.show', $vehicle))->assertOk();
    expect($vehicle->fresh()->views_count)->toBe(101);

    $this->flushSession();
    $this->get(route('vehicles.show', $vehicle))->assertOk();
    expect($vehicle->fresh()->views_count)->toBe(102);
});

test('the public spec sheet downloads a pdf for a published vehicle', function () {
    $vehicle = Vehicle::factory()->create();

    $response = $this->get(route('vehicles.spec-sheet', $vehicle));

    $response->assertOk();

    expect(strtolower($response->headers->get('content-type', '')))
        ->toContain('application/pdf')
        ->and($response->headers->get('content-disposition', ''))
        ->toContain($vehicle->slug);
});

test('the public spec sheet is not available for hidden vehicles', function () {
    $vehicle = Vehicle::factory()->draft()->create();

    $this->get(route('vehicles.spec-sheet', $vehicle))->assertNotFound();
});
