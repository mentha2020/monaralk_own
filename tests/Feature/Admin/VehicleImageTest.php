<?php

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Services\VehicleImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->service = app(VehicleImageService::class);
});

afterEach(function () {
    Storage::forgetDisk('public');
});

test('storing a photo writes the original plus both width variants', function () {
    $vehicle = Vehicle::factory()->create();

    $image = $this->service->store(
        $vehicle,
        UploadedFile::fake()->image('car.jpg', 2400, 1600),
        'A grey hatchback',
        true,
    );

    $storage = Storage::disk('public');

    expect($storage->exists($image->path))->toBeTrue();

    $original = getimagesize($storage->path($image->path));
    expect($original[0])->toBe(VehicleImageService::ORIGINAL_WIDTH);

    foreach (VehicleImageService::VARIANTS as $suffix => $width) {
        $path = $this->service->variantPath($image->path, $suffix);

        expect($storage->exists($path))->toBeTrue();

        $size = getimagesize($storage->path($path));
        expect($size[0])->toBe($width);
    }

    expect($image->is_cover)->toBeTrue()
        ->and($image->alt)->toBe('A grey hatchback');
});

test('generating variants also writes the 1200x630 social image', function () {
    $vehicle = Vehicle::factory()->create();

    $image = $this->service->store($vehicle, UploadedFile::fake()->image('car.jpg', 2400, 1600));

    $storage = Storage::disk('public');
    $ogPath = $this->service->variantPath($image->path, '-og');

    expect($storage->exists($ogPath))->toBeTrue()
        ->and($image->refresh()->path_og)->toBe($ogPath);

    [$width, $height] = getimagesize($storage->path($ogPath));

    expect($width)->toBe(VehicleImageService::OG_WIDTH)
        ->and($height)->toBe(VehicleImageService::OG_HEIGHT);
});

test('deleting an image removes the social image too', function () {
    $vehicle = Vehicle::factory()->create();
    $image = $this->service->store($vehicle, UploadedFile::fake()->image('car.jpg', 1200, 800));

    $ogPath = $image->refresh()->path_og;

    expect($ogPath)->not->toBeNull()
        ->and(Storage::disk('public')->exists($ogPath))->toBeTrue();

    $image->delete();

    expect(Storage::disk('public')->exists($ogPath))->toBeFalse();
});

test('creating an image row generates its variants through the observer', function () {
    $vehicle = Vehicle::factory()->create();
    $storage = Storage::disk('public');

    $storage->put('vehicles/from-repeater.jpg', jpegBytes(1600, 1000));

    $image = VehicleImage::create([
        'vehicle_id' => $vehicle->getKey(),
        'path' => 'vehicles/from-repeater.jpg',
        'alt' => 'Repeater upload',
        'is_cover' => false,
        'sort_order' => 1,
    ]);

    expect($storage->exists($this->service->variantPath($image->path, '-800w')))->toBeTrue()
        ->and($storage->exists($this->service->variantPath($image->path, '-1600w')))->toBeTrue()
        ->and($image->refresh()->path_800w)->not->toBeNull()
        ->and($image->refresh()->path_1600w)->not->toBeNull();
});

test('the observer keeps exactly one cover on a vehicle', function () {
    $vehicle = Vehicle::factory()->create();

    $first = $this->service->store($vehicle, UploadedFile::fake()->image('one.jpg', 1200, 800), 'one', true);
    $second = $this->service->store($vehicle, UploadedFile::fake()->image('two.jpg', 1200, 800), 'two', true);

    expect($first->refresh()->is_cover)->toBeFalse()
        ->and($second->refresh()->is_cover)->toBeTrue()
        ->and($vehicle->images()->where('is_cover', true)->count())->toBe(1);
});

test('deleting an image removes the original and every variant', function () {
    $vehicle = Vehicle::factory()->create();
    $image = $this->service->store($vehicle, UploadedFile::fake()->image('car.jpg', 1200, 800));

    $paths = [
        $image->path,
        $this->service->variantPath($image->path, '-800w'),
        $this->service->variantPath($image->path, '-1600w'),
    ];

    $storage = Storage::disk('public');

    expect(collect($paths)->every(fn (string $path) => $storage->exists($path)))->toBeTrue();

    $image->delete();

    expect(collect($paths)->every(fn (string $path) => $storage->exists($path)))->toBeFalse()
        ->and(VehicleImage::query()->count())->toBe(0);
});

test('replacing the stored path retires the previous files', function () {
    $vehicle = Vehicle::factory()->create();
    $image = $this->service->store($vehicle, UploadedFile::fake()->image('before.jpg', 1200, 800));

    $previous = [
        $image->path,
        $this->service->variantPath($image->path, '-800w'),
        $this->service->variantPath($image->path, '-1600w'),
    ];

    Storage::disk('public')->put('vehicles/renamed.jpg', jpegBytes(1200, 800));
    $image->update(['path' => 'vehicles/renamed.jpg']);

    $storage = Storage::disk('public');

    expect(collect($previous)->every(fn (string $path) => $storage->exists($path)))->toBeFalse()
        ->and($storage->exists($this->service->variantPath('vehicles/renamed.jpg', '-800w')))->toBeTrue();
});

function jpegBytes(int $width, int $height): string
{
    $canvas = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($canvas, null, 90);
    $bytes = ob_get_clean();
    imagedestroy($canvas);

    return $bytes;
}
