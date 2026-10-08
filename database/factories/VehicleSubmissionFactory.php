<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Models\VehicleSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleSubmissionFactory extends Factory
{
    protected $model = VehicleSubmission::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'data' => [
                'make' => fake()->company(),
                'model' => fake()->words(2, true),
                'year' => fake()->numberBetween(2008, 2025),
                'mileage_km' => fake()->numberBetween(5_000, 180_000),
                'price' => fake()->randomFloat(2, 1_500_000, 45_000_000),
                'condition' => 'used',
                'description' => fake()->paragraph(),
                'location' => fake()->randomElement(['Colombo', 'Kandy', 'Galle']),
            ],
            'images' => null,
            'status' => SubmissionStatus::Pending,
            'notes' => null,
            'approved_vehicle_id' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'ip' => fake()->ipv4(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SubmissionStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => SubmissionStatus::Rejected,
            'notes' => 'Does not meet listing criteria.',
            'reviewed_at' => now(),
        ]);
    }
}
