<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantCatagory extends Model
{
    use HasFactory;

    protected $table = 'merchant_catagory';

    protected $fillable = [
        'cat_name',
        'cat_img',
        'cat_status'
    ];

    public function merchants()
    {
        return $this->hasMany(Merchant::class, 'catagory_id');
    }
}
