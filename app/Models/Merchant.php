<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Merchant extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'merchants'; // Specify the actual table name

   

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mobile',
        'address',
        'status',
        'thumbnail',
        'catagory_id',
        'lat',
        'lang',
        'discount_price'
    ];

    public function setMobileAttribute($value)
{
    $value = preg_replace('/^\+91\s?/', '', trim($value)); // Remove existing +91 if any
    $this->attributes['mobile'] = '+91' . $value;
}

    public function merchantcategory()
    {
        return $this->belongsTo(MerchantCatagory::class, 'catagory_id');
    } 

    public function merchantProducts()
{
    return $this->hasMany(MerchantProduct::class, 'merchant_id');
}
public function products()
{
    return $this->belongsToMany(Product::class, 'merchant_products')
        ->withPivot('stock', 'price', 'discount', 'description');
}

}
