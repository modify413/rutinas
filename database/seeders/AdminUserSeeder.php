<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', 'admin')->exists()) {
            return;
        }

        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'admin',
                'email' => 'admin@example.com',
                'username' => 'admin',
                'role' => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
