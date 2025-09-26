<?php

namespace Database\Seeders;

use App\Models\PaymentBreakdown;
use App\Models\PaymentBreakdownPenalty;
use App\Models\PaymentServiceFee;
use App\Models\PaymentDiscount;
use App\Models\Ruling;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentBreakdownSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $breakdowns = [
            [
                'name' => 'Application Fee',
                'type' => 'fixed',
                'amount' => 0
            ], [
                'name' => 'Customer Deposits',
                'type' => 'fixed',
                'amount' => 0
            ], [
                'name' => 'Materials',
                'type' => 'fixed',
                'amount' => 0
            ], [
                'name' => 'Reconnection Fee',
                'type' => 'fixed',
                'amount' => 0
            ], [
                'name' => 'Back Billing',
                'type' => 'fixed',
                'amount' => 0
            ], [
                'name' => 'Others',
                'type' => 'fixed',
                'amount' => 0
            ]
        ];

        foreach ($breakdowns as $breakdown) {
            PaymentBreakdown::create($breakdown);
        }

        PaymentBreakdownPenalty::create([
            'due_from' => '1',
            'due_to' => '*',
            'amount_type' => 'percentage',
            'amount' => 0.15
        ]);

        PaymentDiscount::create([
            'name' => 'SC Discount',
            'eligible' => 'senior',
            'type' => 'percentage',
            'percentage_of' => 'basic_charge',
            'amount' => 0.05
        ]);
    }
}
