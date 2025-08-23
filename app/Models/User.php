<?php

namespace App\Models;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'password',
        'image',
        'is_active',
        'email',
        'fcm_token'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
       // 'email_verified_at' => 'datetime',    //تحوله لوقت وتاريخ
        'phone_verified_at'=> 'datetime',
        'is_active' => 'boolean',
    ];

    public function customer(){
        return $this->hasOne(Customer::class);
    }
    public function areas()
    {
        return $this->hasMany(DriverAreaService::class,'driver_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function ScanLog()
    {
        return $this->hasMany(Scan_Log::class);
    }

}
