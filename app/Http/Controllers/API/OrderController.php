<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

}
