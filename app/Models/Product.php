<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'tbl_product';

    protected $fillable = [
        'cat_id',
        'subcat_id',
        'title',
        'price',
        'discount_price',
        'status',
        'description',
        'thumbnail'
    ];

    // Relationship with Pcat (tbl_pcat)
    public function productcategory()
    {
        return $this->belongsTo(ProductCategory::class, 'cat_id');
    }

    // Relationship with Subcat (tbl_subcat)
    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'subcat_id');
    }

    public function merchantProducts()
{
    return $this->hasMany(MerchantProduct::class, 'product_id');
}

public function merchantProduct()
{
    // This returns the pivot row for the currently loaded merchant
    return $this->hasOne(MerchantProduct::class, 'product_id', 'id')
        ->where('merchant_id', auth()->id());
}


public function merchants()
{
    return $this->belongsToMany(Merchant::class, 'merchant_products')
        ->wherePivotNull('deleted_at')
        ->withPivot(['stock', 'price', 'merchant_price', 'discount', 'description']);
}


}
