<?php

use App\Livewire\SubmitVehicleWizard;
use App\Models\User;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Models\VehicleSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config(['livewire.temporary_file_upload.disk' => 'public']);

    $this->make = Make::factory()->create();
    $this->model = VehicleModel::factory()->create(['make_id' => $this->make->getKey()]);
    $this->transmission = Transmission::factory()->create();
    $this->fuelType = FuelType::factory()->create();
    $this->bodyType = BodyType::factory()->create();
    $this->color = Color::factory()->create();
});

afterEach(function () {
    Storage::forgetDisk('public');
});

test('the submit page renders the multi step wizard', function () {
    $this->get(route('submit.create'))
        ->assertOk()
        ->assertSee('Tell us about your car', false)
        ->assertSee('Continue', false)
        ->assertSee('Five short steps', false);
});

test('a step cannot be left until every field on it validates', function () {
    $wizard = Livewire::test(SubmitVehicleWizard::class)
        ->call('next')
        ->assertHasErrors(['form.make_id', 'form.model_id', 'form.year', 'form.location', 'form.description'])
        ->assertSet('step', 1);

    $wizard
        ->set('form.make_id', $this->make->getKey())
        ->call('next')
        ->assertHasErrors('form.model_id')
        ->assertSet('step', 1);

    $wizard
        ->set('form.model_id', $this->model->getKey())
        ->set('form.year', 2016)
        ->set('form.condition', 'used')
        ->set('form.location', 'Colombo')
        ->set('form.description', 'One owner, full service history.')
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

test('changing the make clears the model picked for the previous make', function () {
    $otherMake = Make::factory()->create();

    Livewire::test(SubmitVehicleWizard::class)
        ->set('form.make_id', $this->make->getKey())
        ->set('form.model_id', $this->model->getKey())
        ->set('form.make_id', $otherMake->getKey())
        ->assertSet('form.model_id', null);
});

test('a phone number without enough digits stops the contact step', function () {
    Livewire::test(SubmitVehicleWizard::class)
        ->set('step', 4)
        ->set('form.name', 'Nuwan Silva')
        ->set('form.email', 'nuwan@example.lk')
        ->set('form.phone', '123')
        ->call('next')
        ->assertHasErrors('form.phone')
        ->assertSet('step', 4);
});

test('a signed in seller starts the wizard with their details filled in', function () {
    $user = User::factory()->create(['name' => 'Amara Fernando', 'email' => 'amara@example.lk']);

    Livewire::actingAs($user)
        ->test(SubmitVehicleWizard::class)
        ->assertSet('form.name', 'Amara Fernando')
        ->assertSet('form.email', 'amara@example.lk');
});

test('the review step summarises the listing before anything is stored', function () {
    Livewire::test(SubmitVehicleWizard::class)
        ->set('form.make_id', $this->make->getKey())
        ->set('form.model_id', $this->model->getKey())
        ->set('form.year', 2016)
        ->set('form.condition', 'used')
        ->set('form.location', 'Colombo')
        ->set('form.description', 'One owner, full service history.')
        ->call('next')
        ->set('form.mileage_km', 62000)
        ->set('form.price', 6450000)
        ->set('form.transmission_id', $this->transmission->getKey())
        ->set('form.fuel_type_id', $this->fuelType->getKey())
        ->set('form.body_type_id', $this->bodyType->getKey())
        ->set('form.exterior_color_id', $this->color->getKey())
        ->set('form.trim', 'Aqua G')
        ->set('form.owners_count', 1)
        ->call('next')
        ->set('photos', [UploadedFile::fake()->image('aqua-1.jpg')])
        ->call('next')
        ->set('form.name', 'Nuwan Silva')
        ->set('form.email', 'nuwan@example.lk')
        ->call('next')
        ->assertSet('step', 5)
        ->assertSee($this->make->name, false)
        ->assertSee('LKR 6,450,000', false)
        ->assertSee('One owner, full service history.', false)
        ->assertSee('62,000 km', false)
        ->assertSee('Nuwan Silva', false);
});

test('a complete wizard run stores one pending submission with its photos', function () {
    $wizard = Livewire::test(SubmitVehicleWizard::class)
        ->set('form.make_id', $this->make->getKey())
        ->set('form.model_id', $this->model->getKey())
        ->set('form.year', 2016)
        ->set('form.condition', 'used')
        ->set('form.location', 'Colombo')
        ->set('form.description', 'One owner, full service history.')
        ->call('next')
        ->set('form.mileage_km', 62000)
        ->set('form.price', 6450000)
        ->set('form.transmission_id', $this->transmission->getKey())
        ->set('form.fuel_type_id', $this->fuelType->getKey())
        ->set('form.trim', 'Aqua G')
        ->set('form.registration_number', 'CBM-1234')
        ->set('form.owners_count', 1)
        ->call('next')
        ->set('photos', [
            UploadedFile::fake()->image('aqua-1.jpg'),
            UploadedFile::fake()->image('aqua-2.jpg'),
        ])
        ->call('next')
        ->set('form.name', 'Nuwan Silva')
        ->set('form.email', 'nuwan@example.lk')
        ->set('form.phone', '+94 77 123 4567')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('step', 6);

    expect(VehicleSubmission::query()->count())->toBe(1);

    $submission = VehicleSubmission::query()->firstOrFail();

    $wizard
        ->assertSee($submission->reference(), false)
        ->assertSee('Submission received', false);

    expect($submission->status)->toBe(SubmissionStatus::Pending)
        ->and($submission->name)->toBe('Nuwan Silva')
        ->and($submission->email)->toBe('nuwan@example.lk')
        ->and($submission->phone)->toBe('+94 77 123 4567')
        ->and($submission->user_id)->toBeNull()
        ->and($submission->ip)->not->toBeNull()
        ->and($submission->data['year'])->toBe(2016)
        ->and((float) $submission->data['price'])->toBe(6450000.0)
        ->and($submission->data['make_id'])->toBe($this->make->getKey())
        ->and($submission->data['model_id'])->toBe($this->model->getKey())
        ->and($submission->data['trim'])->toBe('Aqua G')
        ->and($submission->data)->not->toHaveKey('name')
        ->and($submission->data)->not->toHaveKey('email')
        ->and($submission->approved_vehicle_id)->toBeNull()
        ->and($submission->reviewed_at)->toBeNull()
        ->and(count($submission->images))->toBe(2);

    Storage::disk('public')->assertExists($submission->images[0]);
    Storage::disk('public')->assertExists($submission->images[1]);
});

test('a signed in seller is linked to their submission', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SubmitVehicleWizard::class)
        ->set('form.make_id', $this->make->getKey())
        ->set('form.model_id', $this->model->getKey())
        ->set('form.year', 2018)
        ->set('form.condition', 'used')
        ->set('form.location', 'Kandy')
        ->set('form.description', 'Well maintained.')
        ->call('next')
        ->set('form.mileage_km', 41000)
        ->set('form.price', 5200000)
        ->set('form.transmission_id', $this->transmission->getKey())
        ->set('form.fuel_type_id', $this->fuelType->getKey())
        ->call('next')
        ->set('photos', [UploadedFile::fake()->image('car.jpg')])
        ->call('next')
        ->call('next')
        ->call('submit');

    expect(VehicleSubmission::query()->firstOrFail()->user_id)->toBe($user->getKey());
});

test('pressing submit twice cannot create a second submission', function () {
    $wizard = Livewire::test(SubmitVehicleWizard::class)
        ->set('form.make_id', $this->make->getKey())
        ->set('form.model_id', $this->model->getKey())
        ->set('form.year', 2018)
        ->set('form.condition', 'new')
        ->set('form.location', 'Galle')
        ->set('form.description', 'Brand new arrival.')
        ->call('next')
        ->set('form.mileage_km', 120)
        ->set('form.price', 8900000)
        ->set('form.transmission_id', $this->transmission->getKey())
        ->set('form.fuel_type_id', $this->fuelType->getKey())
        ->call('next')
        ->set('photos', [UploadedFile::fake()->image('new.jpg')])
        ->call('next')
        ->set('form.name', 'Sahan')
        ->set('form.email', 'sahan@example.lk')
        ->call('next')
        ->call('submit')
        ->call('submit')
        ->call('submit');

    expect(VehicleSubmission::query()->count())->toBe(1);
});
