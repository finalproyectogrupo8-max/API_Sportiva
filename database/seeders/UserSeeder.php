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
        // forceFill: is_platform_admin e is_active no son asignables en masa, y firstOrCreate los ignoraba.
        $this->seedUser('admin@sportiva.com', 'Admin User', 'admin123', isPlatformAdmin: true);
        $this->seedUser('general@sportiva.com', 'General User', 'general123', isPlatformAdmin: false);
    }

    private function seedUser(string $email, string $name, string $password, bool $isPlatformAdmin): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        if ($user->exists) {
            return;
        }

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'is_platform_admin' => $isPlatformAdmin,
            'is_active' => true,
        ])->save();
    }
}
