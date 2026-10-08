<?php

namespace App\Modules\Catalog\Imports;

use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class VehicleImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithUpsertColumns, WithUpserts, WithValidation
{
    /**
     * @var array<int, string>
     */
    protected array $failures = [];

    public function __construct(protected ?int $userId = null) {}

    /**
     * @return array<int, string>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = 'Row '.$failure->row().': '.implode(', ', array_values($failure->errors()));
        }
    }

    public function model(array $row): Model
    {
        $make = $this->resolve(Make::class, $row['make'] ?? null);
        $model = $this->resolve(VehicleModel::class, $row['model'] ?? null, ['make_id' => $make->getKey()]);

        $vehicle = new Vehicle([
            'slug' => $this->nullable($row['slug'] ?? null),
            'user_id' => $this->userId,
            'source' => 'admin',
            'make_id' => $make->getKey(),
            'model_id' => $model->getKey(),
            'body_type_id' => $this->resolveId(BodyType::class, $row['body_type'] ?? null),
            'fuel_type_id' => $this->resolveId(FuelType::class, $row['fuel_type'] ?? null),
            'transmission_id' => $this->resolveId(Transmission::class, $row['transmission'] ?? null),
            'exterior_color_id' => $this->resolveId(Color::class, $row['exterior_color'] ?? null),
            'interior_color_id' => $this->resolveId(Color::class, $row['interior_color'] ?? null),
            'trim' => $this->nullable($row['trim'] ?? null),
            'year' => (int) ($row['year'] ?? 0),
            'mileage_km' => (int) ($row['mileage_km'] ?? 0),
            'price' => (float) ($row['price'] ?? 0),
            'condition' => VehicleCondition::tryFrom((string) ($row['condition'] ?? ''))?->value ?? VehicleCondition::Used->value,
            'status' => VehicleStatus::tryFrom((string) ($row['status'] ?? ''))?->value ?? VehicleStatus::Draft->value,
            'is_featured' => filter_var($row['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'location' => $this->nullable($row['location'] ?? null),
            'vin' => $this->nullable($row['vin'] ?? null),
            'registration_number' => $this->nullable($row['registration_number'] ?? null),
            'description' => $this->nullable($row['description'] ?? null),
            'published_at' => $this->nullable($row['published_at'] ?? null),
            'views_count' => 0,
        ]);

        $vehicle->setRelation('make', $make);
        $vehicle->setRelation('model', $model);

        if (blank($vehicle->slug)) {
            $vehicle->slug = Vehicle::uniqueVehicleSlug($vehicle);
        }

        return $vehicle;
    }

    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'price' => ['required', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'make.required' => 'Every row needs a make.',
            'model.required' => 'Every row needs a model.',
            'year.required' => 'Every row needs a year.',
            'price.required' => 'Every row needs a price.',
        ];
    }

    public function uniqueBy(): string
    {
        return 'slug';
    }

    public function upsertColumns(): array
    {
        return [
            'user_id',
            'source',
            'make_id',
            'model_id',
            'body_type_id',
            'fuel_type_id',
            'transmission_id',
            'exterior_color_id',
            'interior_color_id',
            'trim',
            'year',
            'mileage_km',
            'price',
            'condition',
            'status',
            'is_featured',
            'location',
            'vin',
            'registration_number',
            'description',
            'published_at',
        ];
    }

    protected function resolve(string $class, mixed $name, array $extra = []): Model
    {
        $name = trim((string) $name);

        if ($name === '') {
            throw new InvalidArgumentException('A taxonomy name is required to resolve ['.$class.'].');
        }

        $existing = $class::query()
            ->where('name', $name)
            ->when($extra !== [], fn ($query) => $query->where($extra))
            ->first();

        if ($existing instanceof Model) {
            return $existing;
        }

        return $class::create($extra + ['name' => $name, 'is_active' => true, 'sort_order' => 0]);
    }

    protected function resolveId(string $class, mixed $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $this->resolve($class, $name)->getKey();
    }

    protected function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
