<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            AccountSeeder::class,
            RoleSeeder::class,
            PropertyTypeSeeder::class,
            TicketsCategorySeeder::class,
            BaseRateSeeder::class,
            RateSeeder::class,
            BillSeeder::class,
            ConcessionerAccountSeeder::class,
            UserSeeder::class,
            ZoneSeeder::class,
        ]);

    }
}
