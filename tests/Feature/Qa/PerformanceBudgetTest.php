<?php

use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $make = Make::factory()->create(['name' => 'Budget Make']);

    Vehicle::factory()->count(24)->create(['make_id' => $make->getKey()]);

    Vehicle::query()->take(8)->get()->each(function (Vehicle $vehicle) {
        VehicleImage::factory()->count(2)->create(['vehicle_id' => $vehicle->getKey()]);
    });
});

test('the hot pages stay inside their first load html budget', function (string $uri, int $budgetKilobytes) {
    $started = microtime(true);

    $response = $this->get($uri)->assertOk();

    $elapsedMs = (microtime(true) - $started) * 1000;
    $kilobytes = strlen($response->getContent()) / 1024;

    expect($kilobytes)->toBeLessThanOrEqual($budgetKilobytes)
        ->and($elapsedMs)->toBeLessThan(3000);
})->with([
    'home' => ['/', 140],
    'search' => ['/vehicles', 160],
    'filtered search' => ['/vehicles?keyword=Budget', 180],
]);

test('the compiled first load css and js stay inside budget', function () {
    $manifestPath = base_path('public/build/manifest.json');

    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Run `npm run build` to measure the compiled asset budget.');
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);

    $css = base_path('public/build/'.($manifest['resources/css/app.css']['file'] ?? ''));
    $js = base_path('public/build/'.($manifest['resources/js/app.js']['file'] ?? ''));

    $gzip = fn (string $path): int => strlen(gzencode(file_get_contents($path), 9));

    expect(file_exists($css))->toBeTrue()
        ->and(file_exists($js))->toBeTrue()
        ->and($gzip($css))->toBeLessThanOrEqual(20 * 1024)
        ->and($gzip($js))->toBeLessThanOrEqual(25 * 1024);
});

test('the detail page only ever references a bounded image variant', function () {
    $vehicle = Vehicle::query()->firstOrFail();
    VehicleImage::factory()->cover()->create(['vehicle_id' => $vehicle->getKey()]);

    $html = $this->get(route('vehicles.show', $vehicle))->assertOk()->getContent();

    preg_match_all('/<img\b[^>]*?(?<![:\w-])src="([^"]+)"/i', $html, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $src) {
        $bounded = str_contains($src, '-800w')
            || str_contains($src, '-1600w')
            || str_contains($src, '-og');

        expect($bounded)->toBeTrue("Unbounded image variant referenced: {$src}");
    }
});

test('the hot pages answer inside a generous server budget', function (string $uri) {
    $this->get($uri)->assertOk();

    $runs = [];
    for ($i = 0; $i < 3; $i++) {
        $started = microtime(true);
        $this->get($uri)->assertOk();
        $runs[] = (microtime(true) - $started) * 1000;
    }

    expect(min($runs))->toBeLessThan(1500);
})->with(['/', '/vehicles']);

test('a search page keeps a bounded query count with a full catalogue behind it', function () {
    $this->get('/vehicles')->assertOk();

    DB::enableQueryLog();
    $this->get('/vehicles')->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThanOrEqual(20);
});
