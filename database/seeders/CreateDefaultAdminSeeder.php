<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateDefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('SUPER_ADMIN'));
        $password = (string) env('MDP_SUPER_ADMIN');

        if ($email === '' || $password === '') {
            Log::error('Super admin non créé : SUPER_ADMIN et MDP_SUPER_ADMIN sont obligatoires.');

            return;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Super administrateur',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'is_super_admin' => true,
                'status' => 'active',
            ]);
        } else {
            $user->password = Hash::make($password);
            $user->role = 'super_admin';
            $user->is_super_admin = true;
            $user->save();
        }

        Setting::set('super_admin_id', (string) $user->id);
    }
}
