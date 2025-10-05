<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_discount', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('eligible', [
                'pwd',
                'senior',
                'franchise'
            ]);
            $table->enum('type', [
                'percentage',
                'fixed'
            ]);
            $table->enum('percentage_of', [
                'basic_charge',
                'total_amount'
            ])->nullable();
            $table->float('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_discount');
    }
};
