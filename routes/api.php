<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DriverAuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\MerchantController;
use App\Http\Controllers\API\MerchantCategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [DriverAuthController::class, 'logout']);
    Route::get('/delvery-boy/profile', [DriverAuthController::class, 'profile']); // Example profile route
    Route::get('/delvery-boy/orders', [OrderController::class, 'orders']);
    Route::get('/delvery-boy/updateStatus', [DriverAuthController::class, 'updateDriverProfileStatus']); 

    // Cart routes
    Route::post('/cart', [CartController::class, 'addToCart']); // Add to cart
    Route::get('/cart', [CartController::class, 'viewCart']); // View cart
    Route::put('/cart/{cartId}', [CartController::class, 'updateCart']); // Update cart item
    Route::delete('/cart/{cartId}', [CartController::class, 'removeFromCart']); // Remove from cart

    // Order routes
    Route::post('/order', [OrderController::class, 'createOrder']); // Create order from cart
    Route::get('/orders', [OrderController::class, 'getUserOrders']); // Get user orders
    Route::put('/order/{orderId}/status', [OrderController::class, 'updateOrderStatus']);

     // Get all addresses for the authenticated user
     Route::get('addresses', [AddressController::class, 'index']);
    
     // Add a new address
     Route::post('addresses', [AddressController::class, 'store']);
     
     // Update an existing address
     Route::put('addresses/{id}', [AddressController::class, 'update']);
     
     // Delete an address
     Route::delete('addresses/{id}', [AddressController::class, 'destroy']);
     
     // Mark an address as primary
     Route::put('addresses/{id}/primary', [AddressController::class, 'setPrimary']);

     Route::post('/merchant/add-products', [MerchantController::class, 'addProductsToMerchant']);

     Route::post('/merchant/products', [ProductController::class, 'getMerchantProducts']);

});

Route::post('/delvery-boy/login', [DriverAuthController::class, 'login']);
Route::post('/user/login', [MerchantController::class, 'firebaseLogin']);
Route::post('/user/verifyOtp', [MerchantController::class, 'verifyOtp']);
Route::get('/categories-with-merchants', [MerchantCategoryController::class, 'index']);
Route::get('/merchant/{merchantId}/products', [ProductController::class, 'getProductsByMerchant']);
Route::get('/products', [ProductController::class, 'getAllProducts']);
Route::get('/merchants', [MerchantController::class, 'getMerchants']);
Route::get('/categories-with-subcategories', [CategoryController::class, 'getCategoriesWithSubcategories']);
