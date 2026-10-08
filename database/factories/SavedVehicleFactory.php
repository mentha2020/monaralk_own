<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Accounts\Enums\SavedType;
use App\Modules\Accounts\Models\SavedVehicle;
use App\Modules\Catalog\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedVehicleFactory extends Factory
{
    protected $model = SavedVehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'type' => SavedType::Favourite,
        ];
    }

    public function compare(): static
    {
        return $this->state(fn () => ['type' => SavedType::Compare]);
    }
}
