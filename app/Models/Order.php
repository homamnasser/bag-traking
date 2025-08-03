<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
       'user_id',
        'meal1_id',
        'meal2_id',
        'order_date',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function meal1()
    {
        return $this->belongsTo(Meal::class, 'meal1_id');
    }

    public function meal2()
    {
        return $this->belongsTo(Meal::class, 'meal2_id');
    }
}
