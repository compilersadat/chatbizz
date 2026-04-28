<?php

namespace App\Http\Controllers\API;

use App\Helpers\FcmHelper;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Merchant;
use App\Models\MerchantAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Rider;
use App\Models\ServiceRequest;
use App\Services\CashFreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected CashFreeService $cashfreeService;

    public function __construct(CashFreeService $cashfreeService)
    {
        $this->cashfreeService = $cashfreeService;
    }

    public function createWithCashfreeOrder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'address_id' => 'required|integer',
            'contact_name' => 'required|string',
            'contact_number' => 'required|string',
            'email' => 'nullable|email',
            'sub_total' => 'required|numeric',
            'delivery_charges' => 'required|numeric',
            'platform_fee' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'shop_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.product_name' => 'nullable|string',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'drop_lat' => 'required',
            'drop_lng' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $merchantTransactionId = 'ORD_' . now()->format('YmdHis') . '_' . mt_rand(1000, 9999);

            $shop = Merchant::findOrFail($request->shop_id);

            $adminAmount = round((float) $request->platform_fee, 2);
            $deliveryAmount = round((float) $request->delivery_charges, 2);
            $merchantAmount = round((float) $request->total_amount - $adminAmount - $deliveryAmount, 2);

            if ($merchantAmount < 0) {
                throw new \Exception('Invalid split calculation. Merchant amount cannot be negative.');
            }

            $order = Order::create([
                'user_id' => $request->user_id,
                'address_id' => $request->address_id,
                'contact_name' => $request->contact_name,
                'contact_number' => $request->contact_number,
                'sub_total' => $request->sub_total,
                'delivery_charges' => $request->delivery_charges,
                'platform_fee' => $request->platform_fee,
                'total_amount' => $request->total_amount,
                'merchant_transaction_id' => $merchantTransactionId,
                'status' => 'pending',
                'payment_status' => 'pending',
                'delivery_partner_id' => null,
                'shop_id' => $request->shop_id,
                'drop_lat' => $request->drop_lat,
                'drop_lng' => $request->drop_lng,
                'pick_lat' => $shop->lat,
                'pick_lng' => $shop->lang,
                'merchant_amount' => $merchantAmount,
                'delivery_amount' => $deliveryAmount,
                'admin_amount' => $adminAmount,
                'payment_gateway' => 'cashfree',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $cfPayload = [
                'order_id' => $merchantTransactionId,
                'order_amount' => round((float) $request->total_amount, 2),
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => (string) $request->user_id,
                    'customer_name' => $request->contact_name,
                    'customer_email' => $request->email ?: ('user' . $request->user_id . '@example.com'),
                    'customer_phone' => $request->contact_number,
                ],
                'order_note' => 'Order #' . $merchantTransactionId,
                'order_meta' => [
                    'return_url' => config('app.url') . '/cashfree/return?order_id={order_id}',
                ],
            ];

            $cfResponse = $this->cashfreeService->createOrder($cfPayload);

            $order->cashfree_order_id = $cfResponse['order_id'] ?? $merchantTransactionId;
            $order->cashfree_payment_session_id = $cfResponse['payment_session_id'] ?? null;
            $order->cashfree_order_status = $cfResponse['order_status'] ?? 'ACTIVE';
            $order->save();

            $merchantToken = DeviceToken::where('user_id', $shop->id)
                ->forUserType('merchant')
                ->value('device_token');

            if ($merchantToken) {
                FcmHelper::send(
                    $merchantToken,
                    'New Order Received',
                    "Order #{$order->merchant_transaction_id} has been placed.",
                    [
                        'order_id' => $order->id,
                        'screen' => 'merchant_order_details',
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'cashfree_order_id' => $order->cashfree_order_id,
                'payment_session_id' => $order->cashfree_payment_session_id,
                'order_amount' => (float) $order->total_amount,
                'currency' => 'INR',
                'environment' => config('services.cashfree.env', 'sandbox'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Cashfree order creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'cashfree_order_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::where('cashfree_order_id', $request->cashfree_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->payment_status === 'paid') {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment already verified',
                    'status' => $order->status,
                ]);
            }

            $cfOrder = $this->cashfreeService->getOrder($order->cashfree_order_id);
            $payments = $this->cashfreeService->getOrderPayments($order->cashfree_order_id);

            $successfulPayment = null;

            if (is_array($payments)) {
                foreach ($payments as $payment) {
                    if (($payment['payment_status'] ?? null) === 'SUCCESS') {
                        $successfulPayment = $payment;
                        break;
                    }
                }
            }

            $isPaid = (($cfOrder['order_status'] ?? null) === 'PAID') || ! empty($successfulPayment);

            if (! $isPaid) {
                $order->payment_status = 'failed';
                $order->status = 'failed';
                $order->cashfree_order_status = $cfOrder['order_status'] ?? null;
                $order->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not successful',
                    'cashfree_order_status' => $cfOrder['order_status'] ?? null,
                ], 400);
            }

            $order->payment_status = 'paid';
            $order->status = 'paid';
            $order->cashfree_order_status = $cfOrder['order_status'] ?? 'PAID';
            $order->payment_gateway_id = $successfulPayment['cf_payment_id'] ?? null;
            $order->paid_at = now();
            $order->save();

            $this->triggerMerchantPayout($order);

            if (! empty($order->delivery_partner_id)) {
                $this->triggerDeliveryPayout($order);
            }

            $deviceToken = DeviceToken::where('user_id', $order->user_id)
                ->where('user_type', 'customer')
                ->value('device_token');

            if ($deviceToken) {
                FcmHelper::send(
                    $deviceToken,
                    'Order Placed!',
                    "Your order #{$order->id} is placed successfully.",
                    ['order_id' => $order->id, 'screen' => 'order_details']
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'payment_id' => $order->payment_gateway_id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Cashfree verify payment failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createServiceWithCashfreeOrder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'pickup_address' => 'required|string',
            'pickup_lat' => 'required|string',
            'pickup_long' => 'required|string',
            'contact_name' => 'required|string',
            'contact_number' => 'required|string',
            'email' => 'nullable|email',
            'amount' => 'required|numeric',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $merchantTransactionId = 'SRV_' . now()->format('YmdHis') . '_' . mt_rand(1000, 9999);

            $service = ServiceRequest::create([
                'user_id' => $request->user_id,
                'pickup_address' => $request->pickup_address,
                'pickup_lat' => $request->pickup_lat,
                'pickup_long' => $request->pickup_long,
                'contact_name' => $request->contact_name,
                'contact_number' => $request->contact_number,
                'note' => $request->note,
                'status' => 'pending',
                'payment_status' => 'pending',
                'amount' => $request->amount,
                'payment_gateway' => 'cashfree',
            ]);

            $cfPayload = [
                'order_id' => $merchantTransactionId,
                'order_amount' => round((float) $request->amount, 2),
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => (string) $request->user_id,
                    'customer_name' => $request->contact_name,
                    'customer_email' => $request->email ?: ('service' . $request->user_id . '@example.com'),
                    'customer_phone' => $request->contact_number,
                ],
                'order_note' => 'Service request #' . $service->id,
                'order_meta' => [
                    'return_url' => config('app.url') . '/cashfree/service-return?order_id={order_id}',
                ],
            ];

            $cfResponse = $this->cashfreeService->createOrder($cfPayload);

            $service->cashfree_order_id = $cfResponse['order_id'] ?? $merchantTransactionId;
            $service->cashfree_payment_session_id = $cfResponse['payment_session_id'] ?? null;
            $service->cashfree_order_status = $cfResponse['order_status'] ?? 'ACTIVE';
            $service->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'service_request_id' => $service->id,
                'cashfree_order_id' => $service->cashfree_order_id,
                'payment_session_id' => $service->cashfree_payment_session_id,
                'order_amount' => (float) $service->amount,
                'currency' => 'INR',
                'environment' => config('services.cashfree.env', 'sandbox'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Cashfree service order creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service request creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyServicePayment(Request $request)
    {
        $request->validate([
            'cashfree_order_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $service = ServiceRequest::where('cashfree_order_id', $request->cashfree_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($service->payment_status === 'paid') {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment already verified',
                    'status' => $service->status,
                ]);
            }

            $cfOrder = $this->cashfreeService->getOrder($service->cashfree_order_id);
            $payments = $this->cashfreeService->getOrderPayments($service->cashfree_order_id);

            $successfulPayment = null;

            if (is_array($payments)) {
                foreach ($payments as $payment) {
                    if (($payment['payment_status'] ?? null) === 'SUCCESS') {
                        $successfulPayment = $payment;
                        break;
                    }
                }
            }

            $isPaid = (($cfOrder['order_status'] ?? null) === 'PAID') || ! empty($successfulPayment);

            if (! $isPaid) {
                $service->payment_status = 'failed';
                $service->status = 'cancelled';
                $service->cashfree_order_status = $cfOrder['order_status'] ?? null;
                $service->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not successful',
                    'cashfree_order_status' => $cfOrder['order_status'] ?? null,
                ], 400);
            }

            $service->payment_status = 'paid';
            $service->status = 'pending';
            $service->cashfree_order_status = $cfOrder['order_status'] ?? 'PAID';
            $service->payment_gateway_id = $successfulPayment['cf_payment_id'] ?? null;
            $service->payment_time = now();
            $service->paid_at = now();
            $service->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service payment verified successfully',
                'payment_id' => $service->payment_gateway_id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Cashfree service verify payment failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service payment verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cashfreeWebhook(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');

        if (! $this->cashfreeService->verifyWebhookSignature($rawBody, $timestamp, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        $payload = json_decode($rawBody, true);

        $cashfreeOrderId = data_get($payload, 'data.order.order_id');
        $paymentStatus = data_get($payload, 'data.payment.payment_status');
        $cfPaymentId = data_get($payload, 'data.payment.cf_payment_id');

        if (! $cashfreeOrderId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook payload',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $order = Order::where('cashfree_order_id', $cashfreeOrderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if ($order->payment_status === 'paid') {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Already processed',
                ]);
            }

            if ($paymentStatus === 'SUCCESS') {
                $order->payment_status = 'paid';
                $order->status = 'paid';
                $order->payment_gateway_id = $cfPaymentId;
                $order->cashfree_order_status = 'PAID';
                $order->paid_at = now();
                $order->save();

                $this->triggerMerchantPayout($order);

                if (! empty($order->delivery_partner_id)) {
                    $this->triggerDeliveryPayout($order);
                }
            } elseif (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'USER_DROPPED'])) {
                $order->payment_status = 'failed';
                $order->status = 'failed';
                $order->cashfree_order_status = $paymentStatus;
                $order->save();
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Cashfree webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markDelivered(Request $request, int $orderId)
    {
        $order = Order::findOrFail($orderId);

        $order->status = 'delivered';
        $order->save();

        if ($order->payment_status === 'paid' && ! empty($order->delivery_partner_id)) {
            $this->triggerDeliveryPayout($order);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order delivered and rider payout triggered',
        ]);
    }

    protected function triggerMerchantPayout(Order $order): void
    {
        if ((float) $order->merchant_amount <= 0) {
            return;
        }

        if ($order->merchant_payout_status === 'SUCCESS') {
            return;
        }

        $merchant = Merchant::findOrFail($order->shop_id);
        $merchantAccount = MerchantAccount::where('user_id', $merchant->id)->first();

        if (! $merchantAccount) {
            throw new \Exception('Merchant account details not found.');
        }

        if (empty($merchantAccount->bank_account_number) || empty($merchantAccount->ifsc_code)) {
            throw new \Exception('Merchant bank details are incomplete.');
        }

        $beneficiaryId = 'merchant_' . $merchant->id;

        $beneficiary = $this->cashfreeService->createOrGetBeneficiary([
            'beneficiary_id' => $beneficiaryId,
            'beneficiary_name' => $merchantAccount->account_holder_name ?: $merchant->name,
            'beneficiary_instrument_details' => [
                'bank_account_number' => $merchantAccount->bank_account_number,
                'bank_ifsc' => $merchantAccount->ifsc_code,
            ],
            'beneficiary_contact_details' => [
                'beneficiary_email' => 'merchant' . $merchant->id . '@example.com',
                'beneficiary_phone' => ! empty($merchant->mobile)
                    ? preg_replace('/^\+91/', '', $merchant->mobile)
                    : '9999999999',
            ],
        ]);

        $transferId = 'M_' . $order->id . '_' . now()->format('His');

        $transfer = $this->cashfreeService->createTransfer([
            'transfer_id' => $transferId,
            'transfer_amount' => round((float) $order->merchant_amount, 2),
            'transfer_mode' => 'imps',
            'beneficiary_details' => [
                'beneficiary_id' => $beneficiary['beneficiary_id'] ?? $beneficiaryId,
            ],
            'remarks' => 'Merchant payout for order ' . $order->merchant_transaction_id,
        ]);

        $order->merchant_payout_beneficiary_id = $beneficiary['beneficiary_id'] ?? $beneficiaryId;
        $order->merchant_payout_id = $transfer['transfer_id'] ?? $transferId;
        $order->merchant_payout_status = $transfer['transfer_status'] ?? 'PROCESSING';

        if (($transfer['transfer_status'] ?? null) === 'SUCCESS') {
            $order->merchant_paid_at = now();
        }

        $order->save();
    }

    protected function triggerDeliveryPayout(Order $order): void
    {
        if ((float) $order->delivery_amount <= 0) {
            return;
        }

        if ($order->delivery_payout_status === 'SUCCESS') {
            return;
        }

        if (empty($order->delivery_partner_id)) {
            return;
        }

        $rider = Rider::find($order->delivery_partner_id);

        if (! $rider) {
            throw new \Exception('Rider not found.');
        }

        $beneficiaryId = 'rider_' . $rider->id;

        if (! empty($rider->upi_id)) {
            $beneficiaryPayload = [
                'beneficiary_id' => $beneficiaryId,
                'beneficiary_name' => $rider->receipt_name ?: $rider->title,
                'beneficiary_instrument_details' => [
                    'vpa' => $rider->upi_id,
                ],
                'beneficiary_contact_details' => [
                    'beneficiary_email' => $rider->email ?: ('rider' . $rider->id . '@example.com'),
                    'beneficiary_phone' => $rider->mobile ?: '9999999999',
                ],
            ];

            $transferMode = 'upi';
        } else {
            if (empty($rider->acc_number) || empty($rider->ifsc)) {
                throw new \Exception('Rider payout details are incomplete.');
            }

            $beneficiaryPayload = [
                'beneficiary_id' => $beneficiaryId,
                'beneficiary_name' => $rider->receipt_name ?: $rider->title,
                'beneficiary_instrument_details' => [
                    'bank_account_number' => $rider->acc_number,
                    'bank_ifsc' => $rider->ifsc,
                ],
                'beneficiary_contact_details' => [
                    'beneficiary_email' => $rider->email ?: ('rider' . $rider->id . '@example.com'),
                    'beneficiary_phone' => $rider->mobile ?: '9999999999',
                ],
            ];

            $transferMode = 'imps';
        }

        $beneficiary = $this->cashfreeService->createOrGetBeneficiary($beneficiaryPayload);

        $transferId = 'D_' . $order->id . '_' . now()->format('His');

        $transfer = $this->cashfreeService->createTransfer([
            'transfer_id' => $transferId,
            'transfer_amount' => round((float) $order->delivery_amount, 2),
            'transfer_mode' => $transferMode,
            'beneficiary_details' => [
                'beneficiary_id' => $beneficiary['beneficiary_id'] ?? $beneficiaryId,
            ],
            'remarks' => 'Rider payout for order ' . $order->merchant_transaction_id,
        ]);

        $order->delivery_payout_beneficiary_id = $beneficiary['beneficiary_id'] ?? $beneficiaryId;
        $order->delivery_payout_id = $transfer['transfer_id'] ?? $transferId;
        $order->delivery_payout_status = $transfer['transfer_status'] ?? 'PROCESSING';

        if (($transfer['transfer_status'] ?? null) === 'SUCCESS') {
            $order->delivery_paid_at = now();
        }

        $order->save();
    }
}
