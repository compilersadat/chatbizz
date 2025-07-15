<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'address_id', 'contact_name', 'contact_number',
        'sub_total', 'delivery_charges', 'platform_fee', 'total_amount',
        'merchant_transaction_id', 'status', 'delivery_partner_id', 'shop_id','razorpay_order_id', 'payment_gateway_id',
        'pick_lat','pick_lng','drop_lng','drop_lat'
    ];

    public function items() { return $this->hasMany(OrderItem::class); }
    public function user() { return $this->belongsTo(Merchant::class); }
    public function address() { return $this->belongsTo(Address::class); }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
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
