<?php

namespace App\Modules\Submissions\Services;

use App\Models\User;
use App\Modules\Catalog\Enums\VehicleSource;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Models\VehicleSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmissionApprover
{
    private const MAP = [
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
        'description',
        'location',
        'vin',
        'registration_number',
        'owners_count',
        'accident_history',
        'warranty',
        'finance_deposit',
        'finance_term_months',
        'finance_apr',
    ];

    public function approve(VehicleSubmission $submission, User $approver): Vehicle
    {
        if ($submission->status === SubmissionStatus::Approved) {
            throw new RuntimeException('Submission has already been approved.');
        }

        return DB::transaction(function () use ($submission, $approver) {
            $vehicle = Vehicle::create($this->mapAttributes($submission));

            foreach ($submission->images ?? [] as $offset => $path) {
                VehicleImage::create([
                    'vehicle_id' => $vehicle->getKey(),
                    'path' => $path,
                    'alt' => $vehicle->slug,
                    'is_cover' => $offset === 0,
                    'sort_order' => $offset,
                ]);
            }

            $submission->update([
                'status' => SubmissionStatus::Approved,
                'approved_vehicle_id' => $vehicle->getKey(),
                'reviewed_by' => $approver->getKey(),
                'reviewed_at' => now(),
            ]);

            return $vehicle;
        });
    }

    public function decide(VehicleSubmission $submission, User $approver, SubmissionStatus $status, ?string $notes = null): void
    {
        $submission->update([
            'status' => $status,
            'notes' => $notes ?? $submission->notes,
            'reviewed_by' => $approver->getKey(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAttributes(VehicleSubmission $submission): array
    {
        $data = $submission->data ?? [];

        if (blank($data['year'] ?? null)) {
            throw new RuntimeException('Submission is missing the required [year] value.');
        }

        if (blank($data['price'] ?? null)) {
            throw new RuntimeException('Submission is missing the required [price] value.');
        }

        $attributes = array_intersect_key($data, array_flip(self::MAP));

        $attributes['make_id'] = $this->taxonomy(Make::class, $data['make_id'] ?? null, $data['make'] ?? null)
            ?? throw new RuntimeException('Submission is missing the required [make] value.');
        $attributes['model_id'] = $this->taxonomy(VehicleModel::class, $data['model_id'] ?? null, $data['model'] ?? null, ['make_id' => $attributes['make_id']])
            ?? throw new RuntimeException('Submission is missing the required [model] value.');

        $attributes['body_type_id'] = $this->taxonomy(BodyType::class, $data['body_type_id'] ?? null, $data['body_type'] ?? null);
        $attributes['fuel_type_id'] = $this->taxonomy(FuelType::class, $data['fuel_type_id'] ?? null, $data['fuel_type'] ?? null);
        $attributes['transmission_id'] = $this->taxonomy(Transmission::class, $data['transmission_id'] ?? null, $data['transmission'] ?? null);
        $attributes['exterior_color_id'] = $this->taxonomy(Color::class, $data['exterior_color_id'] ?? null, $data['exterior_color'] ?? null);

        return $attributes + [
            'user_id' => $submission->user_id,
            'source' => VehicleSource::Public->value,
            'status' => VehicleStatus::Draft->value,
            'views_count' => 0,
            'is_featured' => false,
        ];
    }

    /**
     * Resolves a taxonomy id, falling back to creating the lookup from its name.
     *
     * @param  array<string, mixed>  $extra
     */
    private function taxonomy(string $class, mixed $id, mixed $name, array $extra = []): ?int
    {
        if (filled($id)) {
            return (int) $id;
        }

        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $existing = $class::query()
            ->where('name', $name)
            ->when($extra !== [], fn ($query) => $query->where($extra))
            ->first();

        if ($existing instanceof Model) {
            return $existing->getKey();
        }

        return $class::create($extra + ['name' => $name, 'is_active' => true, 'sort_order' => 0])->getKey();
    }
}
