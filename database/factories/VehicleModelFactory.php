<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VehicleModelFactory extends Factory
{
    protected $model = VehicleModel::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = Str::title($this->faker->words(2, true)).' '.$index;

        return [
            'make_id' => Make::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
