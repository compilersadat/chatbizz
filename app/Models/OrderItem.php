<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'product_id','product_name', 'price', 'quantity'];

    public function order() { return $this->belongsTo(Order::class); }

   // Relation to MerchantProduct
   public function merchantProduct()
   {
       return $this->belongsTo(MerchantProduct::class, 'merchant_product_id');
   }

   // Shortcut to actual product (via MerchantProduct)
   public function product()
   {
       return $this->merchantProduct->product();
   }

   // Shortcut to merchant (via MerchantProduct)
   public function shop()
   {
       return $this->merchantProduct->merchant();
   }

}
