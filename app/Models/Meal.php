<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'ingredients',
        'meal_type',
        'is_active',
        'imgs',
    ];
    public function Orders()
    {
        return $this->hasMany(Order::class);
    }
}
