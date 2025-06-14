<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantProduct extends Model
{
    use HasFactory;

    protected $table = 'merchant_products'; // Define the actual table name

    protected $fillable = [
        'merchant_id',
        'product_id',
        'stock',
        'price',
        'discount',
        'description'
    ]

    /**
     * Define the relationship with Merchant model.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Define the relationship with Product model.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

