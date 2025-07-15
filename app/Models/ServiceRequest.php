<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pickup_address',
        'pickup_lat',
        'pickup_long',
        'contact_name',
        'contact_number',
        'note',
        'status',
        'payment_status',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'payment_time',
        'delivery_partner_id'
    ];

    protected $casts = [
        'payment_time' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(Merchant::class,'user_id');
    }
     /**
     * The assigned delivery partner (Rider).
     */
    public function deliveryPartner()
    {
        return $this->belongsTo(Rider::class, 'delivery_partner_id');
    }
}
