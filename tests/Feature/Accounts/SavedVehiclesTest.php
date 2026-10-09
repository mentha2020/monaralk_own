<?php

use App\Livewire\SaveVehicle;
use App\Models\User;
use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Accounts\Services\SavedVehicles;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Models\VehicleModel;
use Livewire\Livewire;

test('guests get the browser backed save buttons instead of a server component', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('saveActions(', false)
        ->assertSee('monaralk.favourites', false)
        ->assertDontSee('wire:click="toggleFavourite"', false);

    $this->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('data-saved-merge="0"', false);
});

test('the header badges render the signed in counts', function () {
    $user = User::factory()->create();
    $vehicles = Vehicle::factory()->count(3)->create();

    SavedVehicle::factory()->create(['user_id' => $user->getKey(), 'vehicle_id' => $vehicles[0]->getKey()]);
    SavedVehicle::factory()->create(['user_id' => $user->getKey(), 'vehicle_id' => $vehicles[1]->getKey()]);
    SavedVehicle::factory()->compare()->create(['user_id' => $user->getKey(), 'vehicle_id' => $vehicles[2]->getKey()]);

    $this->actingAs($user)
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('data-saved-merge="1"', false)
        ->assertSee('savedBadges(JSON.parse', false)
        ->assertSee('\u0022favourite\u0022:2', false);
});

test('a signed in buyer toggles a favourite on and off from the listing', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($user)
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('wire:click="toggleFavourite"', false);

    Livewire::actingAs($user)
        ->test(SaveVehicle::class, ['vehicleId' => $vehicle->getKey()])
        ->assertSet('favourite', false)
        ->assertSet('compare', false)
        ->call('toggleFavourite')
        ->assertSet('favourite', true)
        ->assertDispatched('saved-changed')
        ->call('toggleFavourite')
        ->assertSet('favourite', false);

    expect(SavedVehicle::query()->where('user_id', $user->getKey())->count())->toBe(0);
});

test('compare is capped at four cars and explains why', function () {
    $user = User::factory()->create();
    $saved = app(SavedVehicles::class);

    foreach (Vehicle::factory()->count(SavedVehicles::COMPARE_CAP)->create() as $vehicle) {
        $saved->toggle($user->getKey(), $vehicle->getKey(), SavedType::Compare);
    }

    $extra = Vehicle::factory()->create();

    expect($saved->counts($user->getKey())['compare'])->toBe(SavedVehicles::COMPARE_CAP);

    $result = $saved->toggle($user->getKey(), $extra->getKey(), SavedType::Compare);

    expect($result['saved'])->toBeFalse()
        ->and($result['message'])->toBe('Compare is limited to 4 cars.')
        ->and($result['counts']['compare'])->toBe(SavedVehicles::COMPARE_CAP);

    Livewire::actingAs($user)
        ->test(SaveVehicle::class, ['vehicleId' => $extra->getKey()])
        ->call('toggleCompare')
        ->assertSet('compare', false)
        ->assertSet('notice', 'Compare is limited to 4 cars.');
});

test('saved vehicles never leak between accounts', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $mine = Vehicle::factory()->create();
    $theirs = Vehicle::factory()->create();

    SavedVehicle::factory()->create(['user_id' => $owner->getKey(), 'vehicle_id' => $mine->getKey()]);

    $this->actingAs($owner)
        ->get(route('saved.favourites'))
        ->assertOk()
        ->assertSee($mine->listingTitle())
        ->assertDontSee($theirs->listingTitle());

    $this->actingAs($other)
        ->get(route('saved.favourites'))
        ->assertOk()
        ->assertDontSee($mine->listingTitle());

    Livewire::actingAs($other)
        ->test(SaveVehicle::class, ['vehicleId' => $mine->getKey()])
        ->assertSet('favourite', false);
});

test('a saved listing whose title needs escaping is still matched on the page', function () {
    $user = User::factory()->create();
    $make = Make::factory()->create(['name' => "O'Reilly & Sons"]);
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey(), 'name' => 'Axia G']);

    $vehicle = Vehicle::factory()->create([
        'make_id' => $make->getKey(),
        'model_id' => $model->getKey(),
        'trim' => 'GX',
    ]);

    SavedVehicle::factory()->create(['user_id' => $user->getKey(), 'vehicle_id' => $vehicle->getKey()]);

    $this->actingAs($user)
        ->get(route('saved.favourites'))
        ->assertOk()
        ->assertSee($vehicle->listingTitle())
        ->assertDontSee("O'Reilly & Sons Axia G GX", false);
});

test('the favourites and compare pages send guests to the login screen', function () {
    $this->get(route('saved.favourites'))->assertRedirect(route('login'));
    $this->get(route('saved.compare'))->assertRedirect(route('login'));
});

test('the compare page lists the chosen cars side by side and stops at the cap', function () {
    $user = User::factory()->create();
    $saved = app(SavedVehicles::class);

    $vehicles = Vehicle::factory()->count(5)->create();
    $cover = VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicles[0]->getKey()]);

    foreach ($vehicles as $vehicle) {
        $saved->toggle($user->getKey(), $vehicle->getKey(), SavedType::Compare);
    }

    $response = $this->actingAs($user)->get(route('saved.compare'));

    $response
        ->assertOk()
        ->assertSee(__('Compare cars'), false)
        ->assertSee($vehicles[0]->listingTitle())
        ->assertSee('/storage/'.$cover->path_800w, false)
        ->assertDontSee($vehicles[4]->listingTitle());

    expect(SavedVehicle::query()->where('user_id', $user->getKey())->where('type', SavedType::Compare)->count())
        ->toBe(SavedVehicles::COMPARE_CAP);
});

test('the merge endpoint folds browser storage into the account without duplicates', function () {
    $user = User::factory()->create();
    $vehicles = Vehicle::factory()->count(6)->create();

    $payload = [
        'favourite' => [$vehicles[0]->getKey(), $vehicles[1]->getKey()],
        'compare' => [$vehicles[2]->getKey(), $vehicles[3]->getKey(), $vehicles[4]->getKey(), $vehicles[5]->getKey(), $vehicles[0]->getKey()],
    ];

    $this->actingAs($user)
        ->postJson(route('saved.merge'), $payload)
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('counts.favourite', 2)
        ->assertJsonPath('counts.compare', 4);

    $this->actingAs($user)
        ->postJson(route('saved.merge'), $payload)
        ->assertOk()
        ->assertJsonPath('counts.favourite', 2)
        ->assertJsonPath('counts.compare', 4);

    expect(SavedVehicle::query()->where('user_id', $user->getKey())->count())->toBe(6);
});

test('the merge endpoint refuses guests and unknown vehicles', function () {
    $user = User::factory()->create();

    $this->postJson(route('saved.merge'), ['favourite' => [1]])
        ->assertUnauthorized();

    $this->actingAs($user)
        ->postJson(route('saved.merge'), ['favourite' => [999999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('favourite.0');
});

test('removing a car from the compare list only touches that account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    SavedVehicle::factory()->compare()->create(['user_id' => $user->getKey(), 'vehicle_id' => $vehicle->getKey()]);
    SavedVehicle::factory()->compare()->create(['user_id' => $other->getKey(), 'vehicle_id' => $vehicle->getKey()]);

    $this->actingAs($other)
        ->from(route('saved.compare'))
        ->delete(route('saved.destroy', [$vehicle, 'compare']))
        ->assertRedirect(route('saved.compare'));

    expect(SavedVehicle::query()->where('vehicle_id', $vehicle->getKey())->count())->toBe(1)
        ->and(SavedVehicle::query()->where('user_id', $user->getKey())->count())->toBe(1);
});
