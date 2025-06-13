<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_pcat';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'status',
    ];

    public function subcategories()
    {
        return $this->hasMany(SubCategory::class, 'cat_id');
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'cat_id');
    }
}
