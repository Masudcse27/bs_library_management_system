<?php

namespace Database\Seeders;

use App\Models\Settings;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (!Settings::first()) {
            Settings::create([
                'max_borrow_duration' => 30,
                'max_borrow_limit' => 3,
                'max_extension_limit' => 2,
                'max_booking_duration' => 7,
                'max_booking_limit' => 3,
            ]);
        }
    }
}
