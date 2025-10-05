<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class DiscountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('discount_type')->insert([
            ['id' => 1, 'discount_name' => 'Senior Citizen Discount'],
            ['id' => 2, 'discount_name' => 'Franchise Discount'],
            ['id' => 3, 'discount_name' => 'PWD Discount'],
        ]);
    }
}

