<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAreaService extends Model
{
    use HasFactory;
    protected $fillable=
        [
            'name',
            'driver_id',
        ];

    public function customers()
    {
        return $this->hasMany(DriverAreaService::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class,'driver_id');
    }
}
