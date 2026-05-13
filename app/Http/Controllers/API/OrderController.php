<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrderItem;
use Razorpay\Api\Api;
use App\Models\Merchant;
use App\Models\MerchantAccount;
use App\Models\MerchantProduct;
use App\Models\Rider;
use App\Models\ServiceRequest;
use App\Helpers\FcmHelper;
use App\Models\DeviceToken;
use App\Services\CashFreeService;


class OrderController extends Controller
{
    protected CashFreeService $cashfreeService;

    public function __construct(CashFreeService $cashfreeService)
    {
        $this->cashfreeService = $cashfreeService;
    }

    public function orders(Request $request)
    {
     $data["orders"] = Order::where('rid',$request->user()->id)->where('o_status',$request->status)->simplePaginate(10);
     return response()->json([
        'data' => $data
    ]);
    }
    
    public function changeOrderStatus(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'status'   => 'required|string',
        'otp'      => $request->status === 'delivered' ? 'required|digits:6' : 'nullable'
    ]);

    $order = Order::with(['user', 'deliveryPartner'])->findOrFail($request->order_id);
    $oldStatus = $order->status;
    $newStatus = $request->status;

    if ($newStatus === 'delivered') {
        if (!$request->has('otp')) {
            return response()->json([
                'success' => false,
                'message' => 'OTP is required for order completion.'
            ], 422);
        }
    
        if ($order->completion_otp !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please check and try again.'
            ], 422);
        }
    
        // Invalidate OTP
        $order->completion_otp = null;
    }

    // Update status
    $order->status = $newStatus;
    $order->save();

    $merchantPayoutTriggered = false;
    $merchantPayoutError = null;
    $deliveryPayoutTriggered = false;
    $deliveryPayoutError = null;

    if (
        $newStatus === 'delivered'
        && $order->payment_status === 'paid'
    ) {
        try {
            $this->triggerOrderMerchantPayout($order);
            $merchantPayoutTriggered = true;
        } catch (\Throwable $e) {
            $merchantPayoutError = $e->getMessage();

            Log::error('Order merchant payout failed', [
                'order_id' => $order->id,
                'merchant_id' => $order->shop_id,
                'error' => $e->getMessage(),
            ]);
        }

        if (! empty($order->delivery_partner_id)) {
            try {
                $this->triggerOrderDeliveryPayout($order);
                $deliveryPayoutTriggered = true;
            } catch (\Throwable $e) {
                $deliveryPayoutError = $e->getMessage();

                Log::error('Order delivery payout failed', [
                    'order_id' => $order->id,
                    'delivery_partner_id' => $order->delivery_partner_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // Notification texts
    $userText = '';
    $driverText = '';
    $userTitle = "Order Update";
    $driverTitle = "Order Update";

    switch ($newStatus) {
        case 'picked_up':
            $userText   = "Your order has been picked up and is on its way!";
            $driverText = "You have picked up the order. Please deliver it to the customer.";
            break;
        case 'in_transit':
            $userText   = "Your order is in transit. Track your delivery in the app.";
            $driverText = "Order is in transit. Keep moving!";
            break;
        case 'delivered':
            $userText   = "Order delivered successfully! Enjoy your items.";
            $driverText = "You have marked the order as delivered. Great job!";
            break;
        case 'rejected':
            $userText   = "Sorry, your order was rejected. Please try again or contact support.";
            $driverText = "You have rejected the order. The customer will be notified.";
            break;
        case 'failed':
            $userText   = "Unfortunately, your order delivery failed. Please contact support.";
            $driverText = "Order delivery failed. Please report the reason to admin.";
            break;
        case 'canceled':
            $userText   = "Your order was canceled. Any paid amount will be refunded if applicable.";
            $driverText = "The order has been canceled.";
            break;
        case 'refunded':
            $userText   = "Your payment has been refunded. Please check your account.";
            $driverText = "A refund has been issued for the order.";
            break;
        default:
            $userText   = "Order status updated: $newStatus";
            $driverText = "Order status updated: $newStatus";
    }

    // Send FCM to USER (customer)
    $userToken = DeviceToken::where('user_id', $order->user_id)
        ->where('user_type', 'customer')
        ->value('device_token');

    if ($userToken && $userText) {
        // Assuming you have a helper like FcmHelper::send($token, $title, $body, $data)
        FcmHelper::send(
            $userToken,
            $userTitle,
            $userText,
            ['order_id' => $order->id, 'status' => $newStatus, 'screen' => 'order_details']
        );
    }

    // Send FCM to DELIVERY PARTNER
    if ($order->delivery_partner_id) {
        $driverToken = DeviceToken::where('user_id', $order->delivery_partner_id)
            ->where('user_type', 'driver')
            ->value('device_token');

        if ($driverToken && $driverText) {
            FcmHelper::send(
                $driverToken,
                $driverTitle,
                $driverText,
                ['order_id' => $order->id, 'status' => $newStatus, 'screen' => 'order_details']
            );
        }
    }

    return response()->json([
        'message' => 'Order updated & notifications sent.',
        'merchant_payout_triggered' => $merchantPayoutTriggered,
        'merchant_payout_error' => $merchantPayoutError,
        'delivery_payout_triggered' => $deliveryPayoutTriggered,
        'delivery_payout_error' => $deliveryPayoutError,
    ]);
}


public function changeServiceStatus(Request $request)
{
    $request->validate([
        'service_id' => 'required|exists:service_requests,id',
        'status'     => 'required|string',
        'otp'        => $request->status === 'completed' ? 'required|digits:6' : 'nullable',
    ]);

    $service   = ServiceRequest::with(['user', 'deliveryPartner'])->findOrFail($request->service_id);
    $oldStatus = $service->status;
    $newStatus = $request->status;

    // Require + validate OTP for completion
    if ($newStatus === 'completed') {
        if (!$request->has('otp')) {
            return response()->json([
                'success' => false,
                'message' => 'OTP is required to complete this service.',
            ], 422);
        }

        if ($service->completion_otp !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please check and try again.',
            ], 422);
        }

        // Invalidate OTP after successful verification
        $service->completion_otp = null;
    }

    // Update status
    $service->status = $newStatus;
    $service->save();

    $payoutTriggered = false;
    $payoutError = null;

    if (
        $newStatus === 'completed'
        && $service->payment_status === 'paid'
        && ! empty($service->delivery_partner_id)
    ) {
        try {
            $this->triggerServiceDeliveryPayout($service);
            $payoutTriggered = true;
        } catch (\Throwable $e) {
            $payoutError = $e->getMessage();

            Log::error('Service delivery payout failed', [
                'service_request_id' => $service->id,
                'delivery_partner_id' => $service->delivery_partner_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Notification templates
    $userTitle   = 'Service Update';
    $providerTitle = 'Service Update';
    $userText    = '';
    $providerText = '';

    switch ($newStatus) {
        case 'inprocess':
            $userText     = 'Your service request is in process.';
            $providerText = 'You have started the service request. Keep the client updated.';
            break;

        case 'completed':
            $userText     = 'Your service has been completed successfully. Thank you!';
            $providerText = 'You have marked the service as completed. Great job!';
            break;

        case 'cancelled':
            $userText     = 'Your service request has been cancelled.';
            $providerText = 'The service request has been cancelled.';
            break;

        default:
            $userText     = "Service status updated: {$newStatus}";
            $providerText = "Service status updated: {$newStatus}";
            break;
    }

    // Send FCM to USER (customer)
    $userToken = DeviceToken::where('user_id', $service->user_id)
        ->where('user_type', 'customer')
        ->value('device_token');

    if ($userToken && $userText) {
        FcmHelper::send(
            $userToken,
            $userTitle,
            $userText,
            [
                'service_id' => $service->id,
                'status'     => $newStatus,
                'screen'     => 'service_details',
            ]
        );
    }

    // Send FCM to PROVIDER
    if ($service->delivery_partner_id) {
        $providerToken = DeviceToken::where('user_id', $service->delivery_partner_id)
            ->where('user_type', 'driver')
            ->value('device_token');

        if ($providerToken && $providerText) {
            FcmHelper::send(
                $providerToken,
                $providerTitle,
                $providerText,
                [
                    'service_id' => $service->id,
                    'status'     => $newStatus,
                    'screen'     => 'service_details',
                ]
            );
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Service updated & notifications sent.',
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'delivery_payout_triggered' => $payoutTriggered,
        'delivery_payout_error' => $payoutError,
    ]);
}

    public function createOrder(Request $request)
    {
        $user = $request->user();

        // Check if the cart is not empty
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty, cannot create an order.'], 400);
        }

        // Start a DB transaction
        DB::beginTransaction();

        try {
            // Create a new order
            $order = Order::create([
                'uid' => $user->id,
                'o_status' => 'pending', // Status could be pending initially
                'odate' => now(),
                'total' => $this->calculateTotal($cartItems),
            ]);

            // Add products to the order
            foreach ($cartItems as $cartItem) {
                $order->orderProducts()->create([
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                ]);
            }

            // Clear the cart after order creation
            Cart::where('user_id', $user->id)->delete();

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Order created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating order.'], 500);
        }
    }

    // Calculate the total price for the order
    private function calculateTotal($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->product->price * $item->quantity;
        }
        return $total;
    }

    // Get orders for the user
    public function getUserOrders()
    {
        $user = $request->user();
        $orders = Order::with('orderProducts.product')->where('uid', $user->id)->get();
        return response()->json($orders);
    }

    // Update order status
    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string|in:pending,completed,canceled',
        ]);

        $order = Order::find($orderId);

        if (!$order || $order->uid != Auth::id()) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update order status
        $order->o_status = $request->status;
        $order->save();

        return response()->json(['message' => 'Order status updated successfully.']);
    }

    public function createWithRazorpayOrder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'address_id' => 'required|integer',
            'contact_name' => 'required|string',
            'contact_number' => 'required|string',
            'sub_total' => 'required|numeric',
            'delivery_charges' => 'required|numeric',
            'platform_fee' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'shop_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'drop_lat' => 'required',
            'drop_lng' => 'required',
        ]);

        $merchantTransactionId = uniqid('ORDER_');

        DB::beginTransaction();
        try {
            $shop = Merchant::where('id', $request->shop_id)->first();
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
                'status' => 'confirmed',
                'delivery_partner_id' => null,
                'shop_id' => $request->shop_id,
                'drop_lat' => $request->drop_lat,
                'drop_lng' => $request->drop_lng,
                'pick_lat' => $shop->lat,
                'pick_lng' => $shop->lang,
                'merchant_amount' => 0,
                'delivery_amount' => round((float) $request->delivery_charges, 2),
                'admin_amount' => round((float) $request->platform_fee, 2),
                'payment_gateway' => 'razorpay',
                'payment_status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $order->merchant_amount = $this->calculateMerchantPriceAmount($order);

            // Create Razorpay order (server-side)
            $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));
            $razorpayOrder = $api->order->create([
                'receipt' => $merchantTransactionId,
                'amount' => intval($request->total_amount * 100), // paise
                'currency' => 'INR',
                'payment_capture' => 1, // auto capture
            ]);
            $order->razorpay_order_id = $razorpayOrder['id'];
            $order->save();

            // Notify merchant about new order
            $merchantToken = DeviceToken::where('user_id', $order->shop_id)
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
                'order_id' => $order->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => intval($request->total_amount * 100),
                'contact_name' => $order->contact_name,
                'contact_number' => $order->contact_number,
                'key_id' => env('RAZORPAY_KEY_ID'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Order creation failed', 'msg' => $e->getMessage()], 500);
        }
    }

    // 2. Verify Razorpay Payment Signature
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $order = Order::where('razorpay_order_id', $request->razorpay_order_id)->firstOrFail();
        $key_secret = env('RAZORPAY_KEY_SECRET');
        $generated_signature = hash_hmac(
            'sha256',
            $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
            $key_secret
        );
        if ($generated_signature === $request->razorpay_signature) {
            $order->status = 'paid';
            $order->payment_status = 'paid';
            $order->payment_gateway_id = $request->razorpay_payment_id;
            $order->save();
            $token =  DeviceToken::where('user_id', $order->user_id)
            ->where('user_type', 'customer')
            ->first();
            $deviceToken = $token->device_token;
            if ($deviceToken) {
                FcmHelper::send(
                    $deviceToken,
                    'Order Placed!',
                    "Your order #{$order->id} is placed successfully.",
                    ['order_id' => $order->id, 'screen' => 'order_details']
                );
            }
            return response()->json(['status' => 'success']);
        } else {
            $order->status = 'failed';
            $order->save();
            return response()->json(['status' => 'fail'], 400);
        }
    }

    public function userOrders(Request $request)
{
    // Get authenticated user
    $user = $request->user();

    // Optionally: filter status, paginate, etc.
    $orders = Order::with([
        'shop',
    ])->where('user_id', $user->id)
      ->orderBy('created_at', 'desc')
      ->paginate(20);

    // Optionally transform if you want to clean up data
    return response()->json([
        'success' => true,
        'data' => $orders,
    ]);
}

public function orderDetails(Request $request, $orderId)
{
    $user = $request->user();

    // Fetch order for this user (secure: ensures user can only access their orders)
    $order = Order::with([
            'orderItems.merchantProduct.product',
            'shop',
            'address',
            'deliveryPartner'
        ])
        ->where('id', $orderId)
        ->where('user_id', $user->id)
        ->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found or access denied.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $order,
    ]);
}


public function userServiceRequests(Request $request)
{
    $perPage = $request->input('per_page', 10);

    // Get only the service requests of the authenticated user
    $query = ServiceRequest::with([
        'deliveryPartner'
    ])->where('user_id', $request->user()->id);

    $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $requests
    ]);
}

public function charges(Request $request){
    $shop = Merchant::where('id', $request->input('shopId'))->first();

    return response()->json([
        'success' => true,
        'delivery_charges' => env('PER_KM_CHARGES'),
        'platform_fee' => env('PLATE_FORM_FEE'),
        'shop_lat' => $shop->lat,
        'shop_long' => $shop->lang
    ]);
}



public function regenerateOtp(Request $request, $orderId)
{
    $order = Order::findOrFail($orderId);

    // Optionally: Check if the order is in a state that allows OTP regeneration (e.g., not completed)
    if ($order->status === 'delivered') {
        return response()->json([
            'success' => false,
            'message' => 'Cannot regenerate OTP for completed order.'
        ], 400);
    }

    // Generate a new 6-digit OTP
    $otp = rand(100000, 999999);

    // Save to the order
    $order->completion_otp = $otp;
    $order->save();

    return response()->json([
        'success' => true,
        'message' => 'OTP regenerated and sent to user.',
        'otp' => app()->isLocal() ? $otp : null // Only send in response if in local env
    ]);
}

protected function triggerOrderMerchantPayout(Order $order): void
{
    $merchantAmount = $this->calculateMerchantPriceAmount($order);

    if ($merchantAmount <= 0) {
        return;
    }

    if ($order->merchant_payout_status === 'SUCCESS') {
        return;
    }

    if ((float) $order->merchant_amount !== $merchantAmount) {
        $order->merchant_amount = $merchantAmount;
        $order->save();
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
        'transfer_amount' => $merchantAmount,
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

protected function calculateMerchantPriceAmount(Order $order): float
{
    $amount = 0.0;

    foreach ($order->orderItems()->get(['product_id', 'quantity']) as $item) {
        $merchantProduct = MerchantProduct::query()
            ->where('merchant_id', $order->shop_id)
            ->where('product_id', $item->product_id)
            ->first();

        if (! $merchantProduct || $merchantProduct->merchant_price === null) {
            throw new \Exception('Merchant price is missing for product ' . $item->product_id . '.');
        }

        $amount += (float) $merchantProduct->merchant_price * (int) $item->quantity;
    }

    return round($amount, 2);
}

protected function triggerServiceDeliveryPayout(ServiceRequest $service): void
{
    if ((float) $service->amount <= 0) {
        return;
    }

    if ($service->delivery_payout_status === 'SUCCESS') {
        return;
    }

    if (empty($service->delivery_partner_id)) {
        return;
    }

    $rider = Rider::find($service->delivery_partner_id);

    if (! $rider) {
        throw new \Exception('Rider not found.');
    }

    $beneficiaryId = 'service_rider_' . $rider->id;

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

    $transferId = 'SD_' . $service->id . '_' . now()->format('His');

    $transfer = $this->cashfreeService->createTransfer([
        'transfer_id' => $transferId,
        'transfer_amount' => round((float) $service->amount, 2),
        'transfer_mode' => $transferMode,
        'beneficiary_details' => [
            'beneficiary_id' => $beneficiary['beneficiary_id'] ?? $beneficiaryId,
        ],
        'remarks' => 'Service payout for request ' . $service->id,
    ]);

    $service->delivery_payout_beneficiary_id = $beneficiary['beneficiary_id'] ?? $beneficiaryId;
    $service->delivery_payout_id = $transfer['transfer_id'] ?? $transferId;
    $service->delivery_payout_status = $transfer['transfer_status'] ?? 'PROCESSING';

    if (($transfer['transfer_status'] ?? null) === 'SUCCESS') {
        $service->delivery_paid_at = now();
    }

    $service->save();
}

protected function triggerOrderDeliveryPayout(Order $order): void
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
