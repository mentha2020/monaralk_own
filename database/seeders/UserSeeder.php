<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@monaralk.lk'],
            [
                'name' => 'Site Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['super_admin']);

        foreach ([
            ['editor@monaralk.lk', 'Site Editor', ['editor']],
            ['viewer@monaralk.lk', 'Site Viewer', ['viewer']],
        ] as [$email, $name, $roles]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles($roles);
        }

        User::factory(3)->create();
    }
}
