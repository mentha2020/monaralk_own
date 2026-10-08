<?php

namespace Database\Factories;

use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        $key = 'setting_'.$this->faker->unique()->numberBetween(1, 1_000_000);

        return [
            'key' => $key,
            'value' => $this->faker->word(),
            'group' => 'general',
            'type' => 'string',
        ];
    }
}
