<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillDiscount extends Model
{
    use HasFactory;

    protected $table = 'bill_discount';
    protected $fillable = [
        'bill_id',
        'name',
        'description',
        'amount'
    ];

}
