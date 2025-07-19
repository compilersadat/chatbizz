<?php
namespace App\Services;

use Razorpay\Api\Api;
use App\Models\MerchantAccount;
use Exception;

class RazorpayXOnboardingService
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
    }

    public function onboard(MerchantAccount $account)
    {
        try {
            // 1. Create Contact
            $contact = $this->api->contact->create([
                'name' => $account->account_holder_name,
                'contact' => $account->user->mobile ?? null,
                'email' => $account->user->email ?? null,
                'type' => 'vendor',
                'reference_id' => 'merchant_' . $account->user_id,
            ]);

            $account->razorpay_contact_id = $contact['id'];
            $account->save();

            // 2. Create Fund Account
            $fundAccount = $this->api->fund_account->create([
                'contact_id' => $contact['id'],
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => $account->account_holder_name,
                    'ifsc' => $account->ifsc_code,
                    'account_number' => $account->bank_account_number,
                ],
            ]);

            $account->razorpay_fund_account_id = $fundAccount['id'];
            $account->verification_status = 'verified';
            $account->save();

            return [
                'success' => true,
                'message' => 'Merchant onboarded successfully.',
                'contact_id' => $contact['id'],
                'fund_account_id' => $fundAccount['id'],
            ];

        } catch (Exception $e) {
            $account->verification_status = 'failed';
            $account->kyc_notes = $e->getMessage();
            $account->save();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
