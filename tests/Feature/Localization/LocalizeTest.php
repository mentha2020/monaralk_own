<?php

use App\Models\User;
use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Accounts\Services\SavedVehicles;
use App\Modules\Catalog\Models\Vehicle;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

function storefrontTranslatableKeys(): array
{
    $keys = [];

    foreach (['resources/views', 'app'] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory)));

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());

            if (preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $matches)) {
                foreach ($matches[1] as $key) {
                    $keys[stripslashes($key)] = true;
                }
            }

            if (preg_match_all('/__\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $code, $matches)) {
                foreach ($matches[1] as $key) {
                    $keys[stripslashes($key)] = true;
                }
            }
        }
    }

    return array_keys($keys);
}

test('the storefront serves english by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('Browse Cars');
});

test('choosing sinhala switches the session the cookie and the rendered copy', function () {
    $response = $this->from(route('vehicles.index'))->get(route('language.switch', 'si'));

    $response->assertRedirect(route('vehicles.index'));
    $response->assertCookie('locale', 'si');
    expect(session('locale'))->toBe('si');

    $this->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('lang="si"', false)
        ->assertSee('රථ බලන්න');
});

test('an unsupported locale never leaves the switcher', function () {
    $this->get('/language/fr')->assertNotFound();

    $this->withSession(['locale' => 'fr'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('lang="en"', false);

    expect(App::getLocale())->toBe('en');
});

test('the admin panel stays in english', function () {
    $this->seed(RoleSeeder::class);

    $this->withSession(['locale' => 'si'])
        ->actingAs(staff('admin'))
        ->get('/admin')
        ->assertOk();

    expect(App::getLocale())->toBe('en');
});

test('money stays in lkr while dates and page numbers follow the locale', function () {
    $vehicle = Vehicle::factory()->create();

    expect($vehicle->formattedPrice)->toMatch('/^LKR [\d,]+$/');

    app()->setLocale('si');
    Carbon::setLocale('si');

    expect($vehicle->formattedPrice)->toMatch('/^LKR [\d,]+$/')
        ->and(Carbon::parse('2026-10-09')->isoFormat('D MMM YYYY'))->toContain('ඔක්')
        ->and(trans('pagination.next'))->toContain('මීළඟ')
        ->and(trans('validation.required', ['attribute' => 'email']))->toContain('email ක්ෂේත්‍රය');
});

test('the compare limit message is translated in both the service and the browser', function () {
    app()->setLocale('si');

    expect(SavedVehicles::capMessage(SavedType::Compare))->toBe('සැසඳීමට රථ 4 ක් දක්වා පමණයි.');

    app()->setLocale('en');

    expect(SavedVehicles::capMessage(SavedType::Compare))->toBe('Compare is limited to 4 cars.');

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('compareFull');
});

test('every translation used by the storefront and the app has a sinhala entry', function () {
    $si = json_decode(File::get(base_path('lang/si.json')), true);

    expect($si)->toBeArray()->not->toBeEmpty();

    foreach ($si as $key => $value) {
        expect($value)->toBeString()->not->toBe('');
    }

    app()->setLocale('si');

    $missing = array_values(array_filter(
        storefrontTranslatableKeys(),
        fn (string $key) => ! array_key_exists($key, $si) && trans($key) === $key,
    ));

    expect(storefrontTranslatableKeys())->not->toBeEmpty()
        ->and($missing)->toBeEmpty();
});

test('the shared api lang exports match the storefront catalogue', function () {
    $si = json_decode(File::get(base_path('lang/si.json')), true);
    $apiSi = json_decode(File::get(base_path('lang/api/si.json')), true);
    $apiEn = json_decode(File::get(base_path('lang/api/en.json')), true);

    expect(array_keys($apiSi))->toBe(array_keys($si))
        ->and(array_keys($apiEn))->toBe(array_keys($si));

    foreach ($apiEn as $key => $value) {
        expect($value)->toBe($key);
    }
});

test('a signed in buyer sees the sinhala favourites page', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    SavedVehicle::factory()->create([
        'user_id' => $user->getKey(),
        'vehicle_id' => $vehicle->getKey(),
    ]);

    $this->withSession(['locale' => 'si'])
        ->actingAs($user)
        ->get(route('saved.favourites'))
        ->assertOk()
        ->assertSee('lang="si"', false)
        ->assertSee($vehicle->listingTitle());
});
