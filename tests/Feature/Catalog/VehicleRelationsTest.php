<?php

use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Feature;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Settings\Models\Setting;

test('factory produces a vehicle whose model belongs to its make', function () {
    $vehicle = Vehicle::factory()->create();

    expect($vehicle->make)->toBeInstanceOf(Make::class)
        ->and($vehicle->model)->toBeInstanceOf(VehicleModel::class)
        ->and($vehicle->model->make_id)->toBe($vehicle->make_id);
});

test('vehicle resolves its taxonomy relations', function () {
    $vehicle = Vehicle::factory()->create();

    expect($vehicle->bodyType)->not->toBeNull()
        ->and($vehicle->fuelType)->not->toBeNull()
        ->and($vehicle->transmission)->not->toBeNull()
        ->and($vehicle->exteriorColor)->toBeInstanceOf(Color::class)
        ->and($vehicle->interiorColor)->toBeInstanceOf(Color::class);
});

test('vehicle belongs to many features through vehicle_features', function () {
    $vehicle = Vehicle::factory()->create();
    $features = Feature::factory()->count(3)->create();

    $vehicle->features()->attach($features->pluck('id'));

    expect($vehicle->fresh()->features)->toHaveCount(3)
        ->and($vehicle->features()->count())->toBe(3);
});

test('vehicle images are ordered and expose a single cover', function () {
    $vehicle = Vehicle::factory()->create();

    foreach ([3, 1, 2] as $position) {
        $vehicle->images()->create([
            'path' => "vehicles/x-{$position}.jpg",
            'sort_order' => $position,
            'is_cover' => $position === 1,
        ]);
    }

    expect($vehicle->images()->pluck('sort_order')->all())->toBe([1, 2, 3])
        ->and($vehicle->coverImage)->not->toBeNull()
        ->and($vehicle->coverImage->is_cover)->toBeTrue();
});

test('vehicle generates a unique slug from make model and year', function () {
    $first = Vehicle::factory()->create(['year' => 2020]);
    $second = Vehicle::factory()->create([
        'make_id' => $first->make_id,
        'model_id' => $first->model_id,
        'year' => 2020,
    ]);

    expect($first->slug)->not->toBe($second->slug)
        ->and($first->slug)->toContain('2020')
        ->and(Vehicle::where('slug', $first->slug)->count())->toBe(1);
});

test('contact phone and whatsapp fall back to settings', function () {
    Setting::set('contact.phone', '+94 77 000 0000', 'contact');
    Setting::set('contact.whatsapp', '+94770000000', 'contact');

    $inherited = Vehicle::factory()->create(['phone' => null, 'whatsapp' => null]);

    expect($inherited->contactPhone)->toBe('+94 77 000 0000')
        ->and($inherited->contactWhatsApp)->toBe('+94770000000');
});

test('vehicle contact overrides take precedence over settings', function () {
    Setting::set('contact.phone', '+94 77 000 0000', 'contact');

    $vehicle = Vehicle::factory()->create([
        'phone' => '+94 11 999 9999',
        'whatsapp' => '+94119999999',
    ]);

    expect($vehicle->contactPhone)->toBe('+94 11 999 9999')
        ->and($vehicle->contactWhatsApp)->toBe('+94119999999');
});

test('whatsapp falls back to the contact phone when neither is set', function () {
    Setting::set('contact.phone', '+94 77 000 0000', 'contact');

    $vehicle = Vehicle::factory()->create(['phone' => null, 'whatsapp' => null]);

    expect($vehicle->contactWhatsApp)->toBe('+94 77 000 0000');
});

test('formatted price is prefixed with lkr and grouped', function () {
    $vehicle = Vehicle::factory()->create(['price' => 8950000]);

    expect($vehicle->formattedPrice)->toBe('LKR 8,950,000');
});
