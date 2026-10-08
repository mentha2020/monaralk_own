<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FuelTypeFactory extends Factory
{
    protected $model = FuelType::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = $this->faker->randomElement([
            'Petrol', 'Diesel', 'Hybrid', 'Electric', 'Petrol Hybrid',
        ]).'-'.$index;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
