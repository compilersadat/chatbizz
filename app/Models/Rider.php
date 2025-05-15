<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Rider extends Authenticatable
{
    use HasApiTokens, HasFactory;


    protected $table = 'tbl_rider';

    protected $fillable = [
        'title',
        'rimg',
        'status',
        'rate',
        'lcode',
        'full_address',
        'pincode',
        'landmark',
        'commission',
        'bank_name',
        'ifsc',
        'receipt_name',
        'acc_number',
        'paypal_id',
        'upi_id',
        'email',
        'rstatus',
        'mobile',
        'accept',
        'reject',
        'complete',
        'dzone',
        'vehiid',
        'adhar_id',
        'password'
    ];

    protected $hidden = ['password'];

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
