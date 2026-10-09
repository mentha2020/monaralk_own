<?php

use App\Livewire\SubmitVehicleWizard;
use App\Modules\Catalog\Filament\Resources\VehicleResource\Pages\CreateVehicle;
use App\Modules\Catalog\Filament\Resources\VehicleResource\Pages\ListVehicles;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Services\SubmissionApprover;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    config(['livewire.temporary_file_upload.disk' => 'public']);
});

afterEach(function () {
    Storage::forgetDisk('public');
});

test('the whole listing journey works from admin sign in to an approved public submission', function () {
    $admin = staff('admin');

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($admin);

    $make = Make::factory()->create(['name' => 'Suzuki']);
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey(), 'name' => 'Alto']);

    Livewire::test(CreateVehicle::class)
        ->fillForm([
            'make_id' => $make->getKey(),
            'model_id' => $model->getKey(),
            'condition' => 'used',
            'year' => 2019,
            'price' => 3150000,
            'trim' => 'E2E SMOKE',
            'location' => 'Gampaha',
            'phone' => '+94 77 555 0199',
            'whatsapp' => '+94 77 555 0199',
            'phone_display' => '077 555 0199',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $listing = Vehicle::query()->where('trim', 'E2E SMOKE')->firstOrFail();

    expect($listing->status->value)->toBe('draft');

    $this->get(route('vehicles.show', $listing))->assertNotFound();

    Livewire::test(ListVehicles::class)
        ->callTableAction('publish', $listing);

    $listing->refresh();

    expect($listing->status->value)->toBe('published')
        ->and($listing->published_at)->not->toBeNull();

    $this->get(route('vehicles.index', ['keyword' => 'E2E SMOKE']))
        ->assertOk()
        ->assertSee('E2E SMOKE');

    $detail = $this->get(route('vehicles.show', $listing))->assertOk();

    $detail->assertSee('E2E SMOKE')
        ->assertSee('Gampaha')
        ->assertSee('tel:', false)
        ->assertSee('wa.me', false)
        ->assertSee(route('vehicles.show', $listing), false);

    $this->post(route('vehicles.enquiry', $listing), [
        'name' => 'Buyer One',
        'email' => 'buyer.one@example.lk',
        'phone' => '+94 77 555 0101',
        'message' => 'Is the Alto still available?',
        'website' => '',
    ])->assertRedirect(route('vehicles.show', $listing));

    $enquiry = Enquiry::query()->firstOrFail();

    expect($enquiry->vehicle_id)->toBe($listing->getKey())
        ->and($enquiry->name)->toBe('Buyer One')
        ->and($enquiry->status->value)->toBe('new');

    $sellerMake = Make::factory()->create(['name' => 'Perodua']);
    $sellerModel = VehicleModel::factory()->create(['make_id' => $sellerMake->getKey(), 'name' => 'Axia']);
    $transmission = Transmission::factory()->create();
    $fuelType = FuelType::factory()->create();
    $bodyType = BodyType::factory()->create();
    $color = Color::factory()->create();

    Livewire::test(SubmitVehicleWizard::class)
        ->set('form.make_id', $sellerMake->getKey())
        ->set('form.model_id', $sellerModel->getKey())
        ->set('form.year', 2017)
        ->set('form.condition', 'used')
        ->set('form.location', 'Kandy')
        ->set('form.description', 'One owner, full service history, ready to go.')
        ->call('next')
        ->set('form.mileage_km', 84000)
        ->set('form.price', 2450000)
        ->set('form.transmission_id', $transmission->getKey())
        ->set('form.fuel_type_id', $fuelType->getKey())
        ->set('form.body_type_id', $bodyType->getKey())
        ->set('form.exterior_color_id', $color->getKey())
        ->set('form.owners_count', 1)
        ->call('next')
        ->set('photos', [UploadedFile::fake()->image('axia-1.jpg')])
        ->call('next')
        ->set('form.name', 'Seller One')
        ->set('form.email', 'seller.one@example.lk')
        ->set('form.phone', '+94 77 555 0202')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    $submission = VehicleSubmission::query()->latest('id')->firstOrFail();

    expect($submission->status->value)->toBe('pending')
        ->and($submission->data['price'])->toBe(2450000);

    $approved = app(SubmissionApprover::class)->approve($submission, $admin);

    expect($submission->refresh()->status->value)->toBe('approved')
        ->and($approved->status->value)->toBe('draft')
        ->and($approved->make->name)->toBe('Perodua')
        ->and($approved->model->name)->toBe('Axia');

    $this->get(route('vehicles.show', $approved))->assertNotFound();

    Livewire::test(ListVehicles::class)
        ->callTableAction('publish', $approved);

    $this->get(route('vehicles.show', $approved->refresh()))
        ->assertOk()
        ->assertSee('Perodua')
        ->assertSee('Axia');
});

test('a buyer who is not signed in can still complete every public step', function () {
    $make = Make::factory()->create();
    $model = VehicleModel::factory()->create(['make_id' => $make->getKey()]);
    $vehicle = Vehicle::factory()->create([
        'make_id' => $make->getKey(),
        'model_id' => $model->getKey(),
        'trim' => 'GUESTFLOW',
    ]);

    $this->get(route('home'))->assertOk()->assertSee('GUESTFLOW');
    $this->get(route('vehicles.index', ['keyword' => 'GUESTFLOW']))->assertOk()->assertSee('GUESTFLOW');
    $this->get(route('vehicles.show', $vehicle))->assertOk()->assertSee('GUESTFLOW');
    $this->get(route('contact'))->assertOk();
    $this->get(route('submit.create'))->assertOk();

    $this->post(route('contact.store'), [
        'name' => 'Guest Buyer',
        'email' => 'guest@example.lk',
        'phone' => '+94 77 555 0303',
        'message' => 'Do you deliver to Negombo?',
        'website' => '',
    ])->assertRedirect(route('contact'));

    expect(Enquiry::query()->count())->toBe(1);
});

test('the admin surfaces that guard the journey stay closed to the public', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('vehicles.show', $vehicle))->assertOk();

    $this->post(route('vehicles.enquiry', $vehicle), [
        'name' => 'Blocked',
        'email' => 'blocked@example.lk',
        'phone' => '+94 77 555 0404',
        'message' => 'Trying to reach an admin endpoint.',
        'website' => 'https://spam.example',
    ]);

    expect(Enquiry::query()->count())->toBe(0);

    $this->get('/admin/vehicles/create')->assertRedirect('/admin/login');
    $this->get('/admin/submissions')->assertRedirect('/admin/login');
});

test('a viewer can watch the journey but not drive it', function () {
    $this->actingAs(staff('viewer'));

    $this->get('/admin/vehicles')->assertOk();
    $this->get('/admin/vehicles/create')->assertForbidden();
});
