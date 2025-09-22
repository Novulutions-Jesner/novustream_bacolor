<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;
    
    protected $guard = 'admins'; 

    protected $fillable = [
        'name',
        'email',
        'user_type',
        'zone_assigned',
        'password',
        'token',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
