<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountType extends Model
{
    protected $table = 'discount_type';

    protected $fillable = ['discount_name'];

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'discount_type');
    }
}
