<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discount';

    protected $fillable = [
        'account_no',
        'id_no',
        'discount_type_id',
        'effective_date',
        'expired_date',
    ];

    public function paymentDiscount()
    {
        return $this->belongsTo(PaymentDiscount::class, 'discount_type');
    }
    public function type()
    {
        return $this->belongsTo(DiscountType::class, 'discount_type_id');
    }

}

