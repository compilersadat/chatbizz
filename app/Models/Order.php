<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    protected $table = 'tbl_order';

    protected $fillable = [
        'uid',
        'rid',
        'cat_id',
        'dzone',
        'vehicleid',
        'pick_address',
        'pick_lat',
        'pick_lng',
        'drop_lat',
        'drop_lng',
        'subtotal',
        'o_total',
        'cou_id',
        'cou_amt',
        'trans_id',
        'o_status',
        'dcommission',
        'wall_amt',
        'p_method_id',
        'odate',
        'rlats',
        'rlongs',
        'delivertime',
        'distance'
    ];

    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'uid');
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class, 'rid');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'cat_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'dzone');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicleid');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'cou_id');
    }
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
    
}
