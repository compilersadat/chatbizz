<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'merchant_products';

    protected $fillable = [
        'merchant_id',
        'product_id',
        'stock',
        'price',
        'discount',     // discounted (final) price if present
        'description',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'discount' => 'decimal:2',
        'stock'    => 'integer',
    ];

    /**
     * Final price = discounted price if set, else original price.
     */
    public function getFinalPriceAttribute(): string
    {
        $final = $this->discount !== null && $this->discount !== ''
            ? (float) $this->discount
            : (float) $this->price;

        return number_format($final, 2, '.', '');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
