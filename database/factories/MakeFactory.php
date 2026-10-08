<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Make;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MakeFactory extends Factory
{
    protected $model = Make::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        $name = $this->faker->company().' '.$index;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'country' => $this->faker->randomElement(['Japan', 'Germany', 'South Korea', 'USA', 'India', 'UK']),
            'logo_path' => null,
            'is_active' => true,
            'sort_order' => $index,
        ];
    }
}
