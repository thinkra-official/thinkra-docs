<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@thinkra.test');

        if (User::where('email', $email)->exists()) {
            $this->command?->warn("Super Admin already exists: {$email}");

            return;
        }

        User::create([
            'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email' => $email,
            'password' => env('SUPER_ADMIN_PASSWORD', 'ChangeMe123!'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->command?->info("Super Admin created: {$email}");
    }
}
