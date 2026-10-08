<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Leads\Enums\EnquiryStatus;
use App\Modules\Leads\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryFactory extends Factory
{
    protected $model = Enquiry::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->paragraph(),
            'status' => EnquiryStatus::New,
            'notes' => null,
            'ip' => fake()->ipv4(),
        ];
    }

    public function general(): static
    {
        return $this->state(fn () => ['vehicle_id' => null]);
    }

    public function fromUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->getKey()]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['status' => EnquiryStatus::Resolved]);
    }
}
