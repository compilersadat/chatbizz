<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Rider extends Authenticatable
{
    use HasApiTokens, HasFactory;


    protected $table = 'riders';

    protected $fillable = [
        'title',
        'mobile',
        'email',
        'password',
        'status',
        'rstatus',
        'rate',
        'rimg',
        'adhar_id',
        'full_address',
        'pincode',
        'landmark',
        'dzone',
        'bank_name',
        'ifsc',
        'receipt_name',
        'acc_number',
        'upi_id',
    ];

    /**
     * The attributes that should be hidden for arrays (like password).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    // Relationship with Zone
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'dzone');
    }

    // // Relationship with Vehicle
    // public function vehicle()
    // {
    //     return $this->belongsTo(Vehicle::class, 'vehiid');
    // }
}
