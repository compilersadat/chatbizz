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
        Order::where('id',$request->order_id)->update(['o_status',$request->order_status]);
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
        ]);

        $merchantTransactionId = uniqid('ORDER_');

        DB::beginTransaction();
        try {
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
                'delivery_partner_id' => null,
                'shop_id' => $request->shop_id,
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
            return response()->json(['status' => 'success']);
        } else {
            $order->status = 'failed';
            $order->save();
            return response()->json(['status' => 'fail'], 400);
        }
    }
}
