<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $table = 'tbl_subcat';

    protected $fillable = [
        'cat_id',
        'title',
        'status',
        'image'
    ];

    // Define the relationship with the Category model
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'cat_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'subcat_id');
    }
}
