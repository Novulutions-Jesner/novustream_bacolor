<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $table = 'bill';
    protected $fillable = [
        'reading_id',
        'reference_no',
        'payment_id',
        'bill_period_from',
        'bill_period_to',
        'previous_unpaid',
        'total',
        'discount',
        'penalty',
        'amount',
        'amount_after_due',
        'amount_paid',
        'change',
        'isPaid',
        'hasPenalty',
        'hasDisconnection',
        'hasDisconnected',
        'date_paid',
        'due_date',
        'payor_name',
        'payment_method',
        'paid_by_reference_no',
        'isChangeForAdvancePayment'
    ];

    protected $casts = [
        'isPaid' => 'boolean',
        'hasPenalty' => 'boolean',
        'hasDisconnection' => 'boolean',
        'hasDisconnected' => 'boolean',
        'isChangeForAdvancePayment' => 'boolean',
        'isHighConsumption' => 'boolean',
    ];

    public function reading() {
        return $this->hasOne(Reading::class, 'id', 'reading_id');
    }

    public function breakdown() {
        return $this->hasMany(BillBreakdown::class, 'bill_id', 'id');
    }

    public function discount() {
        return $this->hasMany(BillDiscount::class, 'bill_id', 'id');
    }
}
