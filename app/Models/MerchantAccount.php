<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank_account_number',
        'ifsc_code',
        'bank_name',
        'bank_branch',
        'razorpay_contact_id',
        'razorpay_fund_account_id',
        'razorpay_virtual_account_id',
        'verification_status',
        'kyc_notes',
    ];

    /**
     * Relationship: MerchantAccount belongs to User
     */
    public function user()
    {
        return $this->belongsTo(Merchant::class);
    }
}
