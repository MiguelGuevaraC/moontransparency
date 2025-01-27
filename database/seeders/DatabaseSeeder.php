<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionSeeder::class);
        $this->call(TypeUserAccessSeeder::class);
        $this->call(UsersSeeders::class);
        $this->call(OdsSeeders::class);
        $this->call(ProyectSeeder::class);
    }
}
