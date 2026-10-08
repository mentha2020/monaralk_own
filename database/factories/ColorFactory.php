<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = $this->faker->randomElement([
            'White', 'Black', 'Silver', 'Grey', 'Red', 'Blue', 'Pearl White', 'Navy',
        ]).'-'.$index;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'hex' => $this->faker->hexColor(),
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
