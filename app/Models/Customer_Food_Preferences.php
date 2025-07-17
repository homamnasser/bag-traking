<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer_Food_Preferences extends Model
{
    use HasFactory;
    protected $fillable=
        [
            'customer_id',
            'preferred_food_type',
            'allergies',
            'health_conditions',
            'dietary_system',
            'daily_calorie_needs',

        ];
    public function customer(){
        return $this->belongsTo(Customer::class);
    }


}
