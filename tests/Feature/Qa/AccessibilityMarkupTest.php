<?php

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function qaMarkup(string $html): array
{
    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $xpath = new DOMXPath($document);

    $images = $xpath->query('//img');
    $imagesWithoutAlt = 0;
    foreach ($images as $image) {
        if (! $image->hasAttribute('alt')) {
            $imagesWithoutAlt++;
        }
    }

    $controls = $xpath->query('//input[not(@type="hidden")]|//select|//textarea');
    $controlsWithoutName = [];
    foreach ($controls as $control) {
        $id = $control->getAttribute('id');
        $labelled = $id !== ''
            && $xpath->query('//label[@for="'.htmlspecialchars($id, ENT_QUOTES).'"]')->length > 0;

        $named = $labelled
            || $control->hasAttribute('aria-label')
            || $control->hasAttribute('aria-labelledby')
            || $control->parentNode->nodeName === 'label'
            || $control->getAttribute('aria-hidden') === 'true';

        if (! $named) {
            $controlsWithoutName[] = $control->nodeName;
        }
    }

    return [
        'h1' => $xpath->query('//h1')->length,
        'images' => $images->length,
        'imagesWithoutAlt' => $imagesWithoutAlt,
        'controls' => $controls->length,
        'controlsWithoutName' => $controlsWithoutName,
        'skipLink' => str_contains($html, 'Skip to content') && str_contains($html, 'href="#main"'),
        'lang' => $xpath->query('//html')->item(0)?->getAttribute('lang') ?? '',
        'noindex' => str_contains($html, 'name="robots"') && str_contains($html, 'noindex'),
        'darkVariants' => substr_count($html, 'dark:'),
        'responsiveBreakpoints' => substr_count($html, 'sm:') + substr_count($html, 'lg:'),
        'focusRings' => substr_count($html, 'focus:ring'),
        'themeToggle' => str_contains($html, 'monaralk-theme'),
    ];
}

function qaVehicleWithCover(): Vehicle
{
    $vehicle = Vehicle::factory()->create();
    VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->getKey()]);

    return $vehicle;
}

dataset('qaPublicPages', [
    'home' => '/',
    'search' => '/vehicles',
    'search filtered' => '/vehicles?condition=used&sort=price_asc',
    'contact' => '/contact',
    'submit' => '/submit',
]);

dataset('qaAuthPages', [
    'login' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
]);

dataset('qaPagesWithImages', [
    'home' => '/',
    'search' => '/vehicles',
]);

dataset('qaPagesWithForms', [
    'contact' => '/contact',
    'submit' => '/submit',
]);

dataset('qaAccountPages', [
    'favourites' => '/favourites',
    'compare' => '/compare',
    'dashboard' => '/dashboard',
    'profile' => '/profile',
]);

test('every public page has exactly one h1', function (string $uri) {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['h1'])->toBe(1);
})->with('qaPublicPages');

test('every public page has a skip link pointing at the main landmark', function (string $uri) {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['skipLink'])->toBeTrue();
})->with('qaPublicPages');

test('every image rendered on a public page carries an alt attribute', function (string $uri) {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['images'])->toBeGreaterThan(0)
        ->and($markup['imagesWithoutAlt'])->toBe(0);
})->with('qaPagesWithImages');

test('every form control on a public page has an accessible name', function (string $uri) {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['controls'])->toBeGreaterThan(0)
        ->and($markup['controlsWithoutName'])->toBe([]);
})->with('qaPagesWithForms');

test('the html element declares the active locale on every public page', function (string $uri) {
    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['lang'])->toBe('en');
})->with('qaPublicPages');

test('every auth page has an h1, a skip link and is kept out of search indexes', function (string $uri) {
    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['h1'])->toBe(1)
        ->and($markup['skipLink'])->toBeTrue()
        ->and($markup['noindex'])->toBeTrue();
})->with('qaAuthPages');

test('every account page has an h1 and a skip link', function (string $uri) {
    $this->actingAs(User::factory()->create());

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['h1'])->toBe(1)
        ->and($markup['skipLink'])->toBeTrue();
})->with('qaAccountPages');

test('the account area is not left light-mode only', function () {
    $this->actingAs(User::factory()->create());

    $markup = qaMarkup($this->get('/dashboard')->assertOk()->getContent());

    expect($markup['darkVariants'])->toBeGreaterThan(0)
        ->and($markup['themeToggle'])->toBeTrue();
});

test('public pages ship responsive layout classes rather than a fixed width', function (string $uri) {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['responsiveBreakpoints'])->toBeGreaterThan(0);
})->with('qaPublicPages');

test('public pages ship dark mode variants rather than a light-only stylesheet', function (string $uri) {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get($uri)->assertOk()->getContent());

    expect($markup['darkVariants'])->toBeGreaterThan(0)
        ->and($markup['themeToggle'])->toBeTrue();
})->with('qaPagesWithForms');

test('interactive storefront controls expose a visible focus ring', function () {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get('/')->assertOk()->getContent());

    expect($markup['focusRings'])->toBeGreaterThan(0);
});

test('primary page landmarks are present on the storefront', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<main id="main"', false)
        ->and($html)->toContain('<header', false)
        ->and($html)->toContain('<footer', false);
});

test('the listing grid still renders cards so the image and alt checks are meaningful', function () {
    qaVehicleWithCover();

    $markup = qaMarkup($this->get('/vehicles')->assertOk()->getContent());

    expect($markup['images'])->toBeGreaterThan(0);
});
