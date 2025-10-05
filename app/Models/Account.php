<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function zone()
    {
        return $this->hasMany(ConcessionerAccount::class, 'zone', 'id');
    }

    public function readings()
    {
        return $this->hasMany(Reading::class, 'account_id');
    }
    public function propertyType()
    {
        return $this->belongsTo(PropertyTypes::class, 'property_type', 'id');
    }

    public function discount()
    {
        return $this->hasOne(Discount::class, 'account_no', 'account_no')
                    ->whereDate('effective_date', '<=', now())
                    ->whereDate('expired_date', '>=', now());
    }


}
