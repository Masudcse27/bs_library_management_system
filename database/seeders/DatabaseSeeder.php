<?php

namespace Database\Seeders;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default settings if not exists
        if (!Settings::first()) {
            Settings::create([
                'max_borrow_duration' => 30,
                'max_borrow_limit' => 3,
                'max_extension_limit' => 2,
                'max_booking_duration' => 7,
                'max_booking_limit' => 3,
            ]);
        }

        // Seed default users if not exists
        if (!User::where('email', 'admin@gmail.com')->exists()) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'username' => 'admin',
                'role' => 'admin',
                'password' => Hash::make('password'), // 🔑 Default password
            ]);
        }

        if (!User::where('email', 'masud@gmail.com')->exists()) {
            User::create([
                'name' => 'Masud Bhuiya',
                'email' => 'masud@gmail.com',
                'username' => 'masud',
                'role' => 'user',
                'password' => Hash::make('password'), // 🔑 Default password
            ]);
        }
    }
}
