<?php

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Settings\Models\Page;

function makePage(array $overrides = []): Page
{
    return Page::create(array_merge([
        'title' => 'About Us',
        'slug' => 'about-us',
        'content' => "Monaralk connects buyers and sellers.\n\nWe verify every listing.",
        'meta_title' => null,
        'meta_description' => null,
        'is_published' => true,
    ], $overrides));
}

test('every storefront page emits the default seo tags', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('<meta name="robots" content="index, follow', false)
        ->assertSee('<meta property="og:url"', false)
        ->assertSee('<meta property="og:site_name"', false)
        ->assertSee('<meta name="twitter:card" content="summary"', false);
});

test('the vehicle detail page emits product open graph and twitter tags', function () {
    $vehicle = Vehicle::factory()->published()->create();
    VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->getKey()]);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('vehicles.show', $vehicle).'"', false)
        ->assertSee('<meta property="og:type" content="product"', false)
        ->assertSee('<meta property="og:url" content="'.route('vehicles.show', $vehicle).'"', false)
        ->assertSee('<meta property="og:image" content="', false)
        ->assertSee('<meta property="og:image:width" content="1200"', false)
        ->assertSee('<meta property="og:image:height" content="630"', false)
        ->assertSee('<meta property="product:price:amount" content="', false)
        ->assertSee('<meta property="product:price:currency" content="LKR"', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image"', false)
        ->assertSee('<meta name="twitter:image" content="', false);
});

test('the vehicle detail page emits validating car json-ld', function () {
    $vehicle = Vehicle::factory()->published()->create();
    VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->getKey()]);

    $content = $this->get(route('vehicles.show', $vehicle))->assertOk()->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $content, $matches);

    expect($matches)->not->toBeEmpty();

    $data = json_decode($matches[1], true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($data['@context'])->toBe('https://schema.org')
        ->and($data['@type'])->toBe('Car')
        ->and($data['name'])->toBe($vehicle->listingTitle())
        ->and($data['url'])->toBe(route('vehicles.show', $vehicle))
        ->and($data['brand']['name'])->toBe($vehicle->make->name)
        ->and($data['model'])->toBe($vehicle->model->name)
        ->and((string) $data['vehicleModelDate'])->toBe((string) $vehicle->year)
        ->and($data['mileageFromOdometer']['value'])->toBe((int) $vehicle->mileage_km)
        ->and($data['mileageFromOdometer']['unitCode'])->toBe('KMT')
        ->and($data['image'])->not->toBeEmpty()
        ->and($data['offers']['@type'])->toBe('Offer')
        ->and($data['offers']['priceCurrency'])->toBe('LKR')
        ->and($data['offers']['price'])->toBe(number_format((float) $vehicle->price, 0, '.', ''))
        ->and($data['offers']['availability'])->toBe('https://schema.org/InStock');
});

test('meta values are escaped exactly once', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Sri Lanka&#039;s', false)
        ->assertDontSee('&amp;#039;', false);
});

test('a vehicle without a social crop still shares its cover image', function () {
    $vehicle = Vehicle::factory()->published()->create();
    VehicleImage::factory()->cover()->create([
        'vehicle_id' => $vehicle->getKey(),
        'path_og' => null,
    ]);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('<meta property="og:image" content="', false)
        ->assertSee('<meta property="og:image:alt"', false)
        ->assertDontSee('og:image:width', false);
});

test('robots.txt allows crawling of public pages and points at the sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/plain');

    $body = $response->getContent();

    expect($body)->toContain('User-agent: *')
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /favourites')
        ->toContain('Disallow: /compare')
        ->toContain('Sitemap: '.route('sitemap'));
});

test('the sitemap lists public vehicles and pages and skips drafts', function () {
    $published = Vehicle::factory()->published()->create();
    $draft = Vehicle::factory()->draft()->create();
    $page = makePage();
    $hidden = makePage(['title' => 'Internal', 'slug' => 'internal-notes', 'is_published' => false]);

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('content-type'))->toContain('xml');

    $body = $response->getContent();

    expect($body)->toContain('<loc>'.route('home').'</loc>')
        ->toContain('<loc>'.route('vehicles.index').'</loc>')
        ->toContain('<loc>'.route('vehicles.show', $published).'</loc>')
        ->toContain('<loc>'.route('pages.show', $page).'</loc>')
        ->toContain('<changefreq>weekly</changefreq>')
        ->toContain('<lastmod>')
        ->not->toContain('<loc>'.route('vehicles.show', $draft).'</loc>')
        ->not->toContain($hidden->slug);
});

test('published static pages render with their own meta and drafts return 404', function () {
    $page = makePage([
        'meta_title' => 'About Monaralk',
        'meta_description' => 'Who we are and what we do.',
    ]);

    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertSee($page->title)
        ->assertSee('<title>About Monaralk | ', false)
        ->assertSee('<meta name="description" content="Who we are and what we do."', false)
        ->assertSee('<link rel="canonical" href="'.route('pages.show', $page).'"', false)
        ->assertSee('We verify every listing.');

    $draft = makePage(['title' => 'Draft', 'slug' => 'draft-page', 'is_published' => false]);

    $this->get(route('pages.show', $draft))->assertNotFound();
});

test('published pages are linked from the storefront footer', function () {
    $page = makePage();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('pages.show', $page));
});

test('a branded 404 page is rendered with noindex', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('<meta name="robots" content="noindex, follow', false);
});

test('the 500 error page renders', function () {
    $html = view('errors.500')->render();

    expect($html)->toContain('Something went wrong')
        ->toContain('<meta name="robots" content="noindex, nofollow');
});

test('listing images carry alt text and lazy loading', function () {
    $vehicle = Vehicle::factory()->published()->create();
    VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->getKey()]);

    $this->get(route('vehicles.index'))
        ->assertOk()
        ->assertSee('loading="lazy"', false)
        ->assertSee('decoding="async"', false)
        ->assertSee('alt="', false);
});
