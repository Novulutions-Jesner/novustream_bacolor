<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeniorDiscount extends Model
{
    use HasFactory;

    protected $table = 'discount';
    protected $fillable = [
        'account_no',
        'id_no',
        'discount_type_id',
        'effective_date',
        'expired_date'
    ];

    public function type()
    {
        return $this->belongsTo(PaymentDiscount::class, 'discount_type');
    }

    // Access amount easily via the relation
    public function getDiscountAmountAttribute()
    {
        return $this->type ? $this->type->amount : null;
    }

}
