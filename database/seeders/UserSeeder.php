<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sportiva.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_platform_admin' => true,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'general@sportiva.com'],
            [
                'name' => 'General User',
                'password' => Hash::make('general123'),
                'role' => 'general',
                'is_platform_admin' => false,
                'is_active' => true,
            ]
        );
    }
}
