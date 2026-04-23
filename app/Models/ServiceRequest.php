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
        'payment_gateway',
        'payment_gateway_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'cashfree_order_id',
        'cashfree_payment_session_id',
        'cashfree_order_status',
        'amount',
        'payment_time',
        'paid_at',
        'delivery_partner_id',
        'completion_otp',
        'delivery_payout_beneficiary_id',
        'delivery_payout_id',
        'delivery_payout_status',
        'delivery_paid_at',
    ];

    protected $casts = [
        'payment_time' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'delivery_paid_at' => 'datetime',
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
