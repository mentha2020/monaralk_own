<?php

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Settings\Filament\Resources\SettingResource\Pages\CreateSetting;
use App\Modules\Settings\Models\Setting;
use App\Modules\Shared\Rules\ContactNumber;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

function contactVehicle(array $attributes = []): Vehicle
{
    return Vehicle::factory()->published()->create(array_merge([
        'phone' => null,
        'whatsapp' => null,
        'phone_display' => null,
    ], $attributes));
}

test('the call action is a normalised tel link and the detail page uses the hero variant', function () {
    $vehicle = contactVehicle(['phone' => '+94 77 123 4567']);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('href="tel:+94771234567"', false)
        ->assertSee('data-contact-actions="hero"', false);
});

test('contact details fall back to the global settings', function () {
    Setting::set('contact.phone', '+94 77 000 0000', 'contact');
    Setting::set('contact.whatsapp', '+94770000000', 'contact');

    $vehicle = contactVehicle();

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('href="tel:+94770000000"', false)
        ->assertSee('wa.me/94770000000?', false);
});

test('a listing contact number wins over the global setting', function () {
    Setting::set('contact.phone', '+94 77 000 0000', 'contact');
    Setting::set('contact.whatsapp', '+94770000000', 'contact');

    $vehicle = contactVehicle(['phone' => '+94 11 999 9999', 'whatsapp' => '+94119999999']);

    $html = $this->get(route('vehicles.show', $vehicle))->assertOk()->getContent();

    expect(str_contains($html, 'href="tel:+94119999999"'))->toBeTrue()
        ->and(str_contains($html, 'wa.me/94119999999?'))->toBeTrue()
        ->and(str_contains($html, 'wa.me/94770000000?'))->toBeFalse();
});

test('the whatsapp link strips formatting and carries the listing message', function () {
    $vehicle = contactVehicle([
        'phone' => '077 123 4567',
        'price' => 4_500_000,
        'trim' => 'GXi',
    ]);

    $expected = 'interested in "'.$vehicle->listingTitle().'" - '
        .number_format(4_500_000).' LKR '.route('vehicles.show', $vehicle);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('https://wa.me/94771234567?text='.e(rawurlencode($expected)), false);
});

test('the share menu opens the facebook sharer with the encoded url and copies the link', function () {
    $vehicle = contactVehicle(['phone' => '+94771234567']);

    $shareUrl = route('vehicles.show', $vehicle);

    $html = $this->get(route('vehicles.show', $vehicle))->assertOk()->getContent();

    expect(str_contains($html, 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl)))->toBeTrue()
        ->and(str_contains($html, 'rel="noopener noreferrer nofollow"'))->toBeTrue()
        ->and(str_contains($html, 'x-on:click="copyLink()"'))->toBeTrue()
        ->and(str_contains($html, 'href="'.$shareUrl.'"'))->toBeTrue();
});

test('contact actions are hidden when no number is configured anywhere', function () {
    Setting::query()->delete();
    Setting::flushCache();

    $vehicle = contactVehicle();

    $detail = $this->get(route('vehicles.show', $vehicle))->assertOk()->getContent();
    $listing = $this->get(route('vehicles.index'))->assertOk()->getContent();
    $home = $this->get(route('home'))->assertOk()->getContent();

    expect(str_contains($detail, 'data-contact-actions'))->toBeFalse()
        ->and(str_contains($detail, 'wa.me/'))->toBeFalse()
        ->and(str_contains($listing, 'data-contact-actions'))->toBeFalse()
        ->and(str_contains($home, 'data-contact-actions'))->toBeFalse();
});

test('every listing card on the storefront carries the card contact actions', function () {
    Setting::set('contact.phone', '+94 77 000 0000', 'contact');

    contactVehicle();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-contact-actions="card"', false);

    $this->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('data-contact-actions="card"', false);
});

test('a listing display label replaces the generic call label', function () {
    $vehicle = contactVehicle([
        'phone' => '+94771234567',
        'phone_display' => 'Call the dealer',
    ]);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('Call the dealer');
});

test('the contact number rule counts digits only', function () {
    $rule = fn (mixed $value): bool => Validator::make(
        ['value' => $value],
        ['value' => [new ContactNumber]]
    )->fails();

    expect($rule('+94 77 123 4567'))->toBeFalse()
        ->and($rule('94771234567'))->toBeFalse()
        ->and($rule(''))->toBeFalse()
        ->and($rule(null))->toBeFalse()
        ->and($rule('123'))->toBeTrue()
        ->and($rule('12345678901234567'))->toBeTrue();
});

test('saving a contact setting rejects a number that is too short', function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(staff('admin'));

    Livewire::test(CreateSetting::class)
        ->fillForm([
            'key' => 'contact.phone',
            'group' => 'contact',
            'type' => 'string',
            'value' => '123',
        ])
        ->call('create')
        ->assertHasFormErrors(['value']);

    Livewire::test(CreateSetting::class)
        ->fillForm([
            'key' => 'contact.phone',
            'group' => 'contact',
            'type' => 'string',
            'value' => '+94 77 123 4567',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Setting::get('contact.phone'))->toBe('+94 77 123 4567');
});
