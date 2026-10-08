<?php

use App\Livewire\VehicleSearch;
use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use Livewire\Livewire;

function searchVehicle(array $attributes = []): Vehicle
{
    return Vehicle::factory()->create(array_merge([
        'status' => VehicleStatus::Published,
        'published_at' => now(),
    ], $attributes));
}

test('the listing page renders the search interface', function () {
    searchVehicle(['trim' => 'SHOWCASETRIM']);

    $this->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('Browse cars')
        ->assertSee('Refine search')
        ->assertSee('SHOWCASETRIM')
        ->assertSee('Sort by', false);
});

test('the listing page filters by make from the query string', function () {
    $toyota = Make::factory()->create(['name' => 'Toyota']);
    $perodua = Make::factory()->create(['name' => 'Perodua']);

    searchVehicle(['make_id' => $toyota->id, 'trim' => 'WANTEDTRIM']);
    searchVehicle(['make_id' => $perodua->id, 'trim' => 'OTHERTRIM']);

    $this->get(route('vehicles.index', ['make_id' => $toyota->id]))
        ->assertOk()
        ->assertSee('WANTEDTRIM')
        ->assertDontSee('OTHERTRIM')
        ->assertSee("clearFilter('make_id')", false);
});

test('the listing page filters by condition price year mileage and keyword', function () {
    searchVehicle([
        'trim' => 'KEEPME',
        'condition' => VehicleCondition::New,
        'price' => 4_000_000,
        'year' => 2022,
        'mileage_km' => 12_000,
        'description' => 'Sunroof ZIPZAPCLASS',
    ]);

    searchVehicle([
        'trim' => 'DROPME',
        'condition' => VehicleCondition::Used,
        'price' => 30_000_000,
        'year' => 2009,
        'mileage_km' => 250_000,
        'description' => 'beaten up',
    ]);

    $this->get(route('vehicles.index', [
        'condition' => 'new',
        'min_price' => 1_000_000,
        'max_price' => 8_000_000,
        'min_year' => 2020,
        'max_year' => 2024,
        'max_mileage' => 50_000,
    ]))
        ->assertOk()
        ->assertSee('KEEPME')
        ->assertDontSee('DROPME');

    $this->get(route('vehicles.index', ['keyword' => 'ZIPZAPCLASS']))
        ->assertOk()
        ->assertSee('KEEPME')
        ->assertDontSee('DROPME');
});

test('the listing page sorts by price', function () {
    searchVehicle(['trim' => 'CHEAPIEST', 'price' => 1_000_000]);
    searchVehicle(['trim' => 'DEAREST', 'price' => 90_000_000]);

    $html = $this->get(route('vehicles.index', ['sort' => 'price_asc']))
        ->assertOk()
        ->getContent();

    expect(strpos($html, 'CHEAPIEST'))
        ->toBeLessThan(strpos($html, 'DEAREST'));

    $html = $this->get(route('vehicles.index', ['sort' => 'price_desc']))
        ->assertOk()
        ->getContent();

    expect(strpos($html, 'DEAREST'))
        ->toBeLessThan(strpos($html, 'CHEAPIEST'));
});

test('the listing page offers an empty state and a clear action', function () {
    searchVehicle(['trim' => 'NOTHINGLIKETHIS']);

    $this->get(route('vehicles.index', ['keyword' => 'QQQ-no-such-car']))
        ->assertOk()
        ->assertSee('No vehicles match those filters')
        ->assertDontSee('NOTHINGLIKETHIS');
});

test('the listing page paginates results', function () {
    foreach (range(1, 13) as $index) {
        searchVehicle([
            'trim' => 'PAGEITEM'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'published_at' => now()->subMinutes(100 - $index),
        ]);
    }

    $pageOne = $this->get(route('vehicles.index'))->assertOk()->getContent();
    $pageTwo = $this->get(route('vehicles.index', ['page' => 2]))->assertOk()->getContent();

    expect($pageOne)
        ->toContain('vehicles found')
        ->toContain('PAGEITEM13')
        ->not->toContain('PAGEITEM01')
        ->and($pageTwo)
        ->toContain('PAGEITEM01');
});

test('the listing page keeps filters in the query string', function () {
    searchVehicle(['trim' => 'SYNCED']);

    $this->get(route('vehicles.index', ['keyword' => 'SYNCED', 'sort' => 'price_asc']))
        ->assertOk()
        ->assertSee("clearFilter('keyword')", false);
});

test('filter state can be cleared from the component', function () {
    $make = Make::factory()->create(['name' => 'Clearable']);

    Livewire::test(VehicleSearch::class, [
        'keyword' => 'hello',
        'make_id' => (string) $make->id,
        'min_price' => '1000000',
    ])
        ->assertSet('keyword', 'hello')
        ->assertSee("clearFilter('keyword')", false)
        ->call('clearFilter', 'make_id')
        ->assertSet('make_id', '')
        ->call('clearAll')
        ->assertSet('keyword', '')
        ->assertSet('make_id', '')
        ->assertSet('min_price', '')
        ->assertSet('sort', 'latest');
});

test('choosing a make resets the dependent model filter', function () {
    $make = Make::factory()->create();

    Livewire::test(VehicleSearch::class, ['model_id' => '9'])
        ->set('make_id', (string) $make->id)
        ->assertSet('model_id', '');
});
