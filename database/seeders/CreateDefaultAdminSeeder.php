<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateDefaultAdminSeeder extends Seeder
{
    public function run()
    {
        $email = env('DEFAULT_ADMIN_EMAIL', 'admin@conza.local');
        $password = env('DEFAULT_ADMIN_PASSWORD', 'admin123');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Administrateur',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
            ]);
        } else {
            // ensure role is admin
            $user->role = 'admin';
            $user->save();
        }

        // store super admin id in settings if missing
        if (! Setting::get('super_admin_id')) {
            Setting::set('super_admin_id', (string) $user->id);
        }
    }
}
