<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable=
        [
        'user_id',
        'area_id',
        'address',
        'subscription_status',
        'subscription_start_date',
        'subscription_expiry_date',
        ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(DriverAreaService::class);
    }

    public function customerFoodPreferences(){
        return $this->hasOne(Customer_Food_Preferences::class);
    }
    protected $casts = [
        'subscription_start_date' => 'datetime',
        'subscription_expiry_date' => 'datetime',
    ];
}
