<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = $this->faker->randomElement([
            'Air Conditioning', 'ABS', 'Airbags', 'Alloy Wheels', 'Sunroof', 'Navigation System',
            'Rear Camera', 'Bluetooth', 'Cruise Control', 'Keyless Entry', 'Leather Seats', 'Fog Lamps',
        ]).'-'.$index;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => null,
            'group_name' => $this->faker->randomElement(['Safety', 'Comfort', 'Entertainment', 'Exterior']),
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
