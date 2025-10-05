<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentBreakdown extends Model
{
    use HasFactory;

    protected $table = 'payment_breakdowns';
    protected $fillable = [
        'name',
        'type',
        'percentage_of',
        'amount'
    ];



}
