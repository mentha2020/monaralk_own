<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            LookupSeeder::class,
            UserSeeder::class,
            VehicleSeeder::class,
            PageSeeder::class,
        ]);
    }
}
