<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Add product to cart
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:tbl_product,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        // Check if the product already exists in the cart
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $request->product_id)
                        ->first();

        if ($cartItem) {
            // Update quantity if product already exists in the cart
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Add new item to the cart
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json(['message' => 'Product added to cart successfully.']);
    }

    // View cart items
    public function viewCart(Request $request)
    {
        $user = $request->user();

        // Get all cart items for the logged-in user
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();

        return response()->json($cartItems);
    }

    // Update cart item quantity
    public function updateCart(Request $request, $cartId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Find the cart item by ID
        $cartItem = Cart::find($cartId);

        if (!$cartItem || $cartItem->user_id != Auth::id()) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        // Update the quantity
        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json(['message' => 'Cart updated successfully.']);
    }

    // Remove item from cart
    public function removeFromCart($cartId)
    {
        $cartItem = Cart::find($cartId);

        if (!$cartItem || $cartItem->user_id != Auth::id()) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart successfully.']);
    }
}

