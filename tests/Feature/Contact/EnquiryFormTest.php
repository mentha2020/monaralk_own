<?php

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Leads\Enums\EnquiryStatus;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Leads\Notifications\EnquiryReceived;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

function enquiryFormPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Nimal Perera',
        'email' => 'nimal@example.lk',
        'phone' => '+94 77 123 4567',
        'message' => 'Is this car still available? Can I see it this weekend?',
        'website' => '',
    ];
}

test('a valid enquiry about a listing is stored, linked and queued to the team', function () {
    $vehicle = Vehicle::factory()->create();
    $admin = staff('admin');

    $response = $this->post(route('vehicles.enquiry', $vehicle), enquiryFormPayload());

    $response
        ->assertRedirect(route('vehicles.show', $vehicle))
        ->assertSessionHas('status');

    expect(Enquiry::query()->count())->toBe(1);

    $enquiry = Enquiry::query()->firstOrFail();

    expect($enquiry->vehicle_id)->toBe($vehicle->getKey())
        ->and($enquiry->user_id)->toBeNull()
        ->and($enquiry->name)->toBe('Nimal Perera')
        ->and($enquiry->email)->toBe('nimal@example.lk')
        ->and($enquiry->phone)->toBe('+94 77 123 4567')
        ->and($enquiry->message)->toBe('Is this car still available? Can I see it this weekend?')
        ->and($enquiry->status)->toBe(EnquiryStatus::New)
        ->and($enquiry->ip)->not->toBeNull();

    Notification::assertSentTo($admin, EnquiryReceived::class);
    expect(EnquiryReceived::class)->toImplement(ShouldQueue::class);
});

test('the general contact form stores an enquiry with no vehicle attached', function () {
    $response = $this->post(route('contact.store'), enquiryFormPayload());

    $response
        ->assertRedirect(route('contact'))
        ->assertSessionHas('status');

    $enquiry = Enquiry::query()->firstOrFail();

    expect($enquiry->vehicle_id)->toBeNull()
        ->and($enquiry->status)->toBe(EnquiryStatus::New);
});

test('a signed in buyer is prefilled on the form and linked to their enquiry', function () {
    $user = User::factory()->create(['name' => 'Kasun Silva', 'email' => 'kasun@example.lk']);

    $this->actingAs($user)
        ->get(route('contact'))
        ->assertOk()
        ->assertSee('kasun@example.lk', false)
        ->assertSee('Kasun Silva', false);

    $this->actingAs($user)->post(route('contact.store'), enquiryFormPayload());

    $enquiry = Enquiry::query()->firstOrFail();

    expect($enquiry->user_id)->toBe($user->getKey())
        ->and($enquiry->name)->toBe('Nimal Perera');
});

test('invalid submissions are rejected without writing anything', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), enquiryFormPayload(['email' => 'not-an-email', 'message' => '']))
        ->assertSessionHasErrors(['email', 'message']);

    $this->from(route('contact'))
        ->post(route('contact.store'), enquiryFormPayload(['phone' => '123']))
        ->assertSessionHasErrors('phone');

    expect(Enquiry::query()->count())->toBe(0);
});

test('a filled honeypot is dropped silently and still looks like a success', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), enquiryFormPayload(['website' => 'https://spam.example']))
        ->assertRedirect(route('contact'))
        ->assertSessionHas('status');

    expect(Enquiry::query()->count())->toBe(0);
});

test('enquiries are rate limited to five attempts per IP every ten minutes', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.store'), enquiryFormPayload(['message' => 'Attempt number '.$attempt]))
            ->assertRedirect(route('contact'));
    }

    expect(Enquiry::query()->count())->toBe(5);

    $this->post(route('contact.store'), enquiryFormPayload(['message' => 'Sixth attempt']))
        ->assertStatus(429);

    expect(Enquiry::query()->count())->toBe(5);
});

test('both enquiry forms render with a honeypot and the shared action', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('name="website"', false)
        ->assertSee(route('contact.store'), false);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('name="website"', false)
        ->assertSee(route('vehicles.enquiry', $vehicle), false);
});
