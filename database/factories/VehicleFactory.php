<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Enums\VehicleSource;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source' => VehicleSource::Admin,
            'make_id' => Make::factory(),
            'model_id' => fn (array $definition) => VehicleModel::factory()
                ->create(['make_id' => $definition['make_id']])
                ->getKey(),
            'body_type_id' => BodyType::factory(),
            'fuel_type_id' => FuelType::factory(),
            'transmission_id' => Transmission::factory(),
            'exterior_color_id' => Color::factory(),
            'interior_color_id' => Color::factory(),
            'trim' => $this->faker->randomElement(['GX', 'GXi', 'EX', 'Sport', 'Premium', 'Limited']),
            'year' => $this->faker->numberBetween(2008, 2025),
            'mileage_km' => $this->faker->numberBetween(5_000, 180_000),
            'price' => $this->faker->randomFloat(2, 1_500_000, 45_000_000),
            'condition' => $this->faker->randomElement([
                VehicleCondition::Used,
                VehicleCondition::Used,
                VehicleCondition::CertifiedPreOwned,
                VehicleCondition::New,
            ]),
            'description' => $this->faker->paragraphs(3, true),
            'vin' => strtoupper($this->faker->unique()->regexify('[A-HJ-NPR-Z0-9]{17}')),
            'registration_number' => strtoupper($this->faker->regexify('[A-Z]{2}-[0-9]{4}')),
            'owners_count' => $this->faker->numberBetween(1, 3),
            'accident_history' => $this->faker->randomElement([null, 'None', 'Minor rear bumper repair']),
            'warranty' => $this->faker->randomElement([null, 'Remaining manufacturer warranty until 2027']),
            'last_service_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'finance_deposit' => $this->faker->randomFloat(2, 500_000, 5_000_000),
            'finance_term_months' => $this->faker->randomElement([12, 24, 36, 48, 60]),
            'finance_apr' => $this->faker->randomFloat(2, 8, 18),
            'phone' => null,
            'whatsapp' => null,
            'phone_display' => null,
            'location' => $this->faker->randomElement([
                'Colombo', 'Gampaha', 'Kandy', 'Galle', 'Negombo', 'Jaffna', 'Kurunegala',
            ]),
            'status' => VehicleStatus::Published,
            'published_at' => now(),
            'is_featured' => false,
            'views_count' => $this->faker->numberBetween(0, 2_000),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => VehicleStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => VehicleStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => VehicleStatus::Pending,
            'published_at' => null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::Sold]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => VehicleStatus::Archived,
            'published_at' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function fromPublicSubmission(): static
    {
        return $this->state(fn () => ['source' => VehicleSource::Public]);
    }
}
