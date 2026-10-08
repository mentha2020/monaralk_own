<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site.name', 'value' => 'Monaralk', 'group' => 'site', 'type' => 'string'],
            ['key' => 'site.tagline', 'value' => "Sri Lanka's trusted car marketplace", 'group' => 'site', 'type' => 'string'],
            ['key' => 'contact.phone', 'value' => '+94 77 123 4567', 'group' => 'contact', 'type' => 'string'],
            ['key' => 'contact.whatsapp', 'value' => '+94771234567', 'group' => 'contact', 'type' => 'string'],
            ['key' => 'contact.email', 'value' => 'hello@monaralk.lk', 'group' => 'contact', 'type' => 'string'],
            ['key' => 'finance.default_deposit', 'value' => '20', 'group' => 'finance', 'type' => 'integer'],
            ['key' => 'finance.default_term_months', 'value' => '60', 'group' => 'finance', 'type' => 'integer'],
            ['key' => 'finance.default_apr', 'value' => '12.5', 'group' => 'finance', 'type' => 'decimal'],
        ];

        foreach ($settings as $setting) {
            Setting::set($setting['key'], $setting['value'], $setting['group'], $setting['type']);
        }
    }
}
