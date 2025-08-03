<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scan_Log extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'bag_id',
        'date',
        'time',
        'status',
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function bag(){
        return $this->belongsTo(Bag::class);
    }
}
