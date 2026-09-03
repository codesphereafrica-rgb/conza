<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@conza.com');
        $adminPassword = env('ADMIN_PASSWORD');

        if ($adminPassword) {
            $admin = User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Administrateur',
                    'password' => $adminPassword,
                    'role' => 'admin',
                    'status' => 'active',
                ]
            );

            \App\Models\Setting::updateOrCreate(
                ['key' => 'super_admin_id'],
                ['value' => (string) $admin->id]
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@conza.cd'],
            [
                'name' => 'Administrateur Conza',
                'password' => 'admin@123',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'role' => 'member',
            'status' => 'active',
        ]);
    }
}
