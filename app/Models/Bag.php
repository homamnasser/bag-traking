<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bag extends Model
{
    use HasFactory;
    protected $fillable = [
        'bag_id'
        , 'status'
        , 'customer_id'
        , 'qr_code_path'
        ,'last_update_at'
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted()
    {
        static::creating(function ($bag) {
            $bag->bag_id = self::generateUniqueBagId();
        });
    }


    public static function generateUniqueBagId()
    {
        do {
            $randomId = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('bag_id', $randomId)->exists());

        return $randomId;
    }
}
