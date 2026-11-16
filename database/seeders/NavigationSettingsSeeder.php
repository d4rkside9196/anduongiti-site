<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Navigation;
use Illuminate\Database\Seeder;

class NavigationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::write('navigation', Navigation::allowedKeys());
    }
}
