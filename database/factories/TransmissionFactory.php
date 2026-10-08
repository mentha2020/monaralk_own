<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Transmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransmissionFactory extends Factory
{
    protected $model = Transmission::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = $this->faker->randomElement([
            'Manual', 'Automatic', 'CVT', 'Dual Clutch',
        ]).'-'.$index;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
