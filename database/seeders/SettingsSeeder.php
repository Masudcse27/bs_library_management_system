<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
