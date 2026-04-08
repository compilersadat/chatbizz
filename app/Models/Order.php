<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_id',
        'contact_name',
        'contact_number',
        'sub_total',
        'delivery_charges',
        'platform_fee',
        'total_amount',
        'merchant_transaction_id',
        'status',
        'payment_status',
        'delivery_partner_id',
        'shop_id',

        // old / existing
        'razorpay_order_id',
        'payment_gateway_id',
        'merchant_payment_settled',
        'rider_payment_settled',
        'pick_lat',
        'pick_lng',
        'drop_lng',
        'drop_lat',
        'completion_otp',

        // Cashfree
        'payment_gateway',
        'cashfree_order_id',
        'cashfree_payment_session_id',
        'cashfree_order_status',
        'paid_at',

        // split amounts
        'merchant_amount',
        'delivery_amount',
        'admin_amount',

        // merchant payout
        'merchant_payout_beneficiary_id',
        'merchant_payout_id',
        'merchant_payout_status',
        'merchant_paid_at',

        // rider payout
        'delivery_payout_beneficiary_id',
        'delivery_payout_id',
        'delivery_payout_status',
        'delivery_paid_at',
    ];

    protected $casts = [
        'sub_total' => 'float',
        'delivery_charges' => 'float',
        'platform_fee' => 'float',
        'total_amount' => 'float',
        'merchant_amount' => 'float',
        'delivery_amount' => 'float',
        'admin_amount' => 'float',
        'paid_at' => 'datetime',
        'merchant_paid_at' => 'datetime',
        'delivery_paid_at' => 'datetime',
        'pick_lat' => 'float',
        'pick_lng' => 'float',
        'drop_lat' => 'float',
        'drop_lng' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function shop()
    {
        return $this->belongsTo(Merchant::class, 'shop_id');
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(Rider::class, 'delivery_partner_id');
    }
}