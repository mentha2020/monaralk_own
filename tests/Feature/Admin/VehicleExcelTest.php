<?php

use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Exports\VehicleExport;
use App\Modules\Catalog\Imports\VehicleImport;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->file = 'testing/monaralk-inventory-'.uniqid().'.xlsx';
});

afterEach(function () {
    Storage::disk('local')->delete($this->file);
});

function inventoryWorkbook(array ...$rows): object
{
    return new class($rows) implements FromArray
    {
        public function __construct(private readonly array $rows) {}

        public function array(): array
        {
            return [VehicleExport::COLUMNS, ...$this->rows];
        }
    };
}

test('the export writes the documented headings and one row per vehicle', function () {
    $vehicle = Vehicle::factory()->create(['is_featured' => true, 'status' => VehicleStatus::Published]);

    expect(Excel::store(new VehicleExport, $this->file, 'local'))->toBeTrue()
        ->and(Storage::disk('local')->exists($this->file))->toBeTrue();

    $sheet = Excel::toCollection(null, $this->file, 'local')->first();

    $headings = $sheet[0]->all();
    $record = collect($sheet[1]->all())
        ->mapWithKeys(fn ($value, int $key) => [$headings[$key] => $value]);

    expect($headings)->toBe(VehicleExport::COLUMNS)
        ->and($record['slug'])->toBe($vehicle->slug)
        ->and($record['make'])->toBe($vehicle->make->name)
        ->and($record['model'])->toBe($vehicle->model->name)
        ->and($record['body_type'])->toBe($vehicle->bodyType->name)
        ->and((int) $record['year'])->toBe($vehicle->year)
        ->and((float) $record['price'])->toBe((float) $vehicle->price)
        ->and((int) $record['is_featured'])->toBe(1)
        ->and((string) $record['status'])->toBe('published');
});

test('export then import round-trips a vehicle without losing data', function () {
    $vehicle = Vehicle::factory()->create(['is_featured' => true]);

    Excel::store(new VehicleExport, $this->file, 'local');
    Vehicle::query()->whereKey($vehicle->getKey())->forceDelete();

    expect(Vehicle::withTrashed()->count())->toBe(0);

    Excel::import(new VehicleImport(staff('admin')->id), Storage::disk('local')->path($this->file));

    expect(Vehicle::query()->count())->toBe(1);

    $restored = Vehicle::query()->firstOrFail();

    expect($restored->slug)->toBe($vehicle->slug)
        ->and($restored->year)->toBe($vehicle->year)
        ->and((float) $restored->price)->toBe((float) $vehicle->price)
        ->and($restored->mileage_km)->toBe($vehicle->mileage_km)
        ->and($restored->make->name)->toBe($vehicle->make->name)
        ->and($restored->model->name)->toBe($vehicle->model->name)
        ->and($restored->trim)->toBe($vehicle->trim)
        ->and($restored->vin)->toBe($vehicle->vin)
        ->and($restored->location)->toBe($vehicle->location)
        ->and($restored->status)->toBe($vehicle->status)
        ->and($restored->is_featured)->toBeTrue();
});

test('importing the same spreadsheet twice keeps a single row', function () {
    $vehicle = Vehicle::factory()->create();

    Excel::store(new VehicleExport, $this->file, 'local');
    $path = Storage::disk('local')->path($this->file);

    Excel::import(new VehicleImport(staff('editor')->id), $path);
    Excel::import(new VehicleImport(staff('editor')->id), $path);

    expect(Vehicle::query()->count())->toBe(1)
        ->and(Vehicle::query()->firstOrFail()->slug)->toBe($vehicle->slug);
});

test('import creates the lookups it has never seen', function () {
    $editor = staff('editor');

    Excel::store(inventoryWorkbook([
        'honda-fit-2015', 'Honda', 'Fit', 'Hatchback', 'Petrol', 'Automatic', 'Silver', null,
        'GP5', 2015, 84000, 5250000, 'used', 'draft', 0, 'Colombo', null, null,
        'Reliable daily driver.', null,
    ]), $this->file, 'local');

    Excel::import(new VehicleImport($editor->id), Storage::disk('local')->path($this->file));

    $vehicle = Vehicle::query()->firstOrFail();

    expect(Vehicle::query()->count())->toBe(1)
        ->and($vehicle->slug)->toBe('honda-fit-2015')
        ->and($vehicle->make->name)->toBe('Honda')
        ->and($vehicle->model->name)->toBe('Fit')
        ->and(Make::query()->where('name', 'Honda')->exists())->toBeTrue()
        ->and(VehicleModel::query()->where('name', 'Fit')->exists())->toBeTrue()
        ->and($vehicle->bodyType)->not->toBeNull()
        ->and($vehicle->user_id)->toBe($editor->id);
});

test('rows without a price are skipped instead of failing the whole import', function () {
    Excel::store(inventoryWorkbook(
        ['good-slug-1', 'Honda', 'Fit', null, null, null, null, null, null, 2015, 84000, 5250000, 'used', 'draft', 0, null, null, null, null, null],
        ['bad-slug-2', 'Honda', 'Jazz', null, null, null, null, null, null, 2014, 91000, null, 'used', 'draft', 0, null, null, null, null, null],
    ), $this->file, 'local');

    $import = new VehicleImport(staff('editor')->id);
    Excel::import($import, Storage::disk('local')->path($this->file));

    expect(Vehicle::query()->count())->toBe(1)
        ->and(Vehicle::query()->firstOrFail()->slug)->toBe('good-slug-1')
        ->and($import->failures())->not->toBeEmpty();
});
