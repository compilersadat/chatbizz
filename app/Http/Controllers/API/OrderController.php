<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;
use Razorpay\Api\Api;
use App\Models\Merchant;
use App\Models\ServiceRequest;
use App\Helpers\FcmHelper;
use App\Models\DeviceToken;


class OrderController extends Controller
{
    //
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
            'status'   => 'required|string'
        ]);
        Order::where('id',$request->order_id)->update(['status' => $request->status]);
        return response()->json(['message' => 'Order updated.']);
    } 

    public function changeServiceStatus(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:service_requests,id',
            'status'   => 'required|string'
        ]);
        ServiceRequest::where('id',$request->service_id)->update(['status' => $request->status]);
        return response()->json(['message' => 'Order updated.']);
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
                'pick_lng' => $shop->lang
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

public function createServiceWithRazorpayOrder(Request $request)
{
    $request->validate([
        'user_id'        => 'required|integer',
        'pickup_address' => 'required|string',
        'pickup_lat'     => 'required|string',
        'pickup_long'    => 'required|string',
        'contact_name'   => 'required|string',
        'contact_number' => 'required|string',
        'amount'         => 'required|numeric',
        'note'           => 'nullable|string',
    ]);

    $transactionId = uniqid('SERVICE_');

    DB::beginTransaction();
    try {
        $service = ServiceRequest::create([
            'user_id'        => $request->user_id,
            'pickup_address' => $request->pickup_address,
            'pickup_lat'     => $request->pickup_lat,
            'pickup_long'    => $request->pickup_long,
            'contact_name'   => $request->contact_name,
            'contact_number' => $request->contact_number,
            'note'           => $request->note,
            'status'         => 'pending',
            'amount'         => $request->amount,
        ]);

        // Razorpay Order creation
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));
        $razorpayOrder = $api->order->create([
            'receipt'         => $transactionId,
            'amount'          => intval($request->amount * 100), // paise
            'currency'        => 'INR',
            'payment_capture' => 1,
        ]);
        $service->razorpay_order_id = $razorpayOrder['id'];
        $service->save();

        DB::commit();
        return response()->json([
            'service_request_id' => $service->id,
            'razorpay_order_id'  => $razorpayOrder['id'],
            'amount'             => intval($request->amount * 100),
            'contact_name'       => $service->contact_name,
            'contact_number'     => $service->contact_number,
            'key_id'             => env('RAZORPAY_KEY_ID'),
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Service request creation failed', 'msg' => $e->getMessage()], 500);
    }
}
public function verifyServicePayment(Request $request)
{
    $request->validate([
        'razorpay_order_id'   => 'required|string',
        'razorpay_payment_id' => 'required|string',
        'razorpay_signature'  => 'required|string',
    ]);

    $service = ServiceRequest::where('razorpay_order_id', $request->razorpay_order_id)->firstOrFail();
    $key_secret = env('RAZORPAY_KEY_SECRET');
    $generated_signature = hash_hmac(
        'sha256',
        $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
        $key_secret
    );
    if ($generated_signature === $request->razorpay_signature) {
        $service->payment_status = 'paid';
        $service->status = 'pending';
        $service->razorpay_payment_id = $request->razorpay_payment_id;
        $service->razorpay_signature = $request->razorpay_signature;
        $service->payment_time = now();
        $service->save();
        return response()->json(['status' => 'success']);
    } else {
        $service->payment_status = 'failed';
        $service->status = 'cancelled';
        $service->save();
        return response()->json(['status' => 'fail'], 400);
    }
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

}
