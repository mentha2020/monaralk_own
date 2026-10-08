<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\BodyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BodyTypeFactory extends Factory
{
    protected $model = BodyType::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = $this->faker->randomElement([
            'Sedan', 'SUV', 'Hatchback', 'Coupe', 'Wagon', 'Pickup', 'Van', 'Crossover',
        ]).'-'.$index;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
