<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleImageFactory extends Factory
{
    protected $model = VehicleImage::class;

    public function definition(): array
    {
        $path = 'vehicles/'.fake()->uuid().'.jpg';

        return [
            'vehicle_id' => Vehicle::factory(),
            'path' => $path,
            'path_800w' => str_replace('.jpg', '-800w.jpg', $path),
            'path_1600w' => str_replace('.jpg', '-1600w.jpg', $path),
            'path_og' => str_replace('.jpg', '-og.jpg', $path),
            'alt' => fake()->sentence(3),
            'is_cover' => false,
            'sort_order' => 1,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn () => ['is_cover' => true]);
    }
}
