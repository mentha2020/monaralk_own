<?php

use App\Models\User;
use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Models\VehicleSubmission;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('the dashboard renders the storefront chrome rather than the stock breeze layout', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Browse Cars')
        ->assertSee('Sell Your Car')
        ->assertSee(route('saved.favourites'))
        ->assertSee(route('profile.edit'))
        ->assertDontSee("You're logged in!");
});

test('the dashboard is a single h1 noindex page with a skip link', function () {
    $this->actingAs(User::factory()->create());

    $markup = $this->get('/dashboard')->assertOk()->getContent();

    expect(substr_count($markup, '<h1'))->toBe(1)
        ->and($markup)->toContain('noindex, nofollow')
        ->and($markup)->toContain('href="#main"');
});

test('a fresh dashboard greets the member and shows zeroed stat tiles', function () {
    $user = User::factory()->create(['name' => 'Kasun']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Welcome back, Kasun.')
        ->assertSee('No submissions yet')
        ->assertSee('No listings yet');
});

test('the dashboard counts favourites compare submissions and listings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $owned = Vehicle::factory()->create(['user_id' => $user->getKey()]);
    $saved = Vehicle::factory()->create();

    SavedVehicle::create([
        'user_id' => $user->getKey(),
        'vehicle_id' => $saved->getKey(),
        'type' => SavedType::Favourite,
    ]);
    SavedVehicle::create([
        'user_id' => $user->getKey(),
        'vehicle_id' => Vehicle::factory()->create()->getKey(),
        'type' => SavedType::Compare,
    ]);
    VehicleSubmission::create([
        'user_id' => $user->getKey(),
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '0771234567',
        'data' => [],
        'images' => [],
        'status' => SubmissionStatus::Pending,
    ]);

    $markup = $this->get('/dashboard')->assertOk()->getContent();

    expect(substr_count($markup, 'tabular-nums'))->toBeGreaterThanOrEqual(4)
        ->and($markup)->toContain('1 pending review')
        ->and($markup)->toContain($owned->listingTitle());
});

test('the dashboard lists a submission with its reference and status badge', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $submission = VehicleSubmission::create([
        'user_id' => $user->getKey(),
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '0771234567',
        'data' => [],
        'images' => [],
        'status' => SubmissionStatus::Approved,
    ]);

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee($submission->reference())
        ->assertSee('Approved');
});

test('the dashboard links a published listing to its detail page but not a draft', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $published = Vehicle::factory()->create([
        'user_id' => $user->getKey(),
        'status' => 'published',
        'published_at' => now(),
    ]);
    $draft = Vehicle::factory()->create([
        'user_id' => $user->getKey(),
        'status' => 'draft',
        'published_at' => null,
    ]);

    $markup = $this->get('/dashboard')->assertOk()->getContent();

    expect($markup)->toContain(route('vehicles.show', $published))
        ->and($markup)->not->toContain(route('vehicles.show', $draft))
        ->and($markup)->toContain('Draft');
});

test('a member without staff access is not offered the admin panel', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Open admin panel')
        ->assertDontSee(url('/admin'), false);
});

test('a staff member is offered the admin panel from the dashboard', function () {
    $this->seed(RoleSeeder::class);

    $this->actingAs(staff('admin'))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Open admin panel')
        ->assertSee(url('/admin'), false);
});

test('the profile page now uses the storefront chrome too', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/profile')
        ->assertOk()
        ->assertSee('Browse Cars')
        ->assertSee('Delete Account')
        ->assertSee('current_password');
});

test('the account pages are kept out of search indexes', function () {
    $this->actingAs(User::factory()->create());

    foreach (['/dashboard', '/profile', '/favourites', '/compare'] as $uri) {
        $markup = $this->get($uri)->assertOk()->getContent();

        expect($markup, "Expected {$uri} to be noindexed.")->toContain('noindex, nofollow');
    }
});

test('the signed-in storefront header offers a dashboard link', function () {
    $this->actingAs(User::factory()->create());

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain(route('dashboard'));
});

test('the logo component declares explicit dimensions so it cannot blow out the nav', function () {
    $html = $this->get('/login')->assertOk()->getContent();

    expect($html)->toContain('h-10 w-10 shrink-0');
});
