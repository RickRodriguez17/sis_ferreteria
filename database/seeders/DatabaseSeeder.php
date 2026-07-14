<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            LocationSeeder::class,
            UnitSeeder::class,
            UserSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
