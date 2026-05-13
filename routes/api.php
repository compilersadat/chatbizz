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
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\StaticPageController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\AdController;

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
    Route::get('/delvery-boy/orders', [DriverAuthController::class, 'driverOrders']);
    Route::get('/delvery-boy/services', [DriverAuthController::class, 'driverServices']);

    Route::get('/delvery-boy/updateStatus', [DriverAuthController::class, 'updateDriverProfileStatus']); 
    Route::post('/delvery-boy/accept-order', [DriverAuthController::class,'AssignDriver']);
    Route::post('/delvery-boy/accept-service', [DriverAuthController::class,'AssignDriverToService']);
    Route::post('/delvery-boy/save-driver-device-token', [DriverAuthController::class, 'saveToken']);
    Route::get('/delvery-boy/order/{orderId}', [DriverAuthController::class, 'orderDetails']);
    Route::post('/delvery-boy/update-order-status', [OrderController::class, 'changeOrderStatus']);
    Route::post('/delvery-boy/update-service-status', [OrderController::class, 'changeServiceStatus']);

    Route::post('/delvery-boy/update-location', [DriverAuthController::class, 'updateLocation']);
    Route::post('/delvery-boy/orders/{order}/regenerate-otp', [OrderController::class, 'regenerateOtp']);

    Route::post('/delvery-boy/delivery-chat-send', [ChatController::class, 'send']);

    Route::post('delivery-chat-send', [ChatController::class, 'send']);

    Route::post('/delvery-boy/service-chat-send', [ChatController::class, 'sendService']);

    Route::post('service-chat-send', [ChatController::class, 'sendService']);


    Route::get('order/{orderId}', [OrderController::class, 'orderDetails']);


    // Cart routes
    Route::post('/cart', [CartController::class, 'addToCart']); // Add to cart
    Route::get('/cart', [CartController::class, 'viewCart']); // View cart
    Route::put('/cart/{cartId}', [CartController::class, 'updateCart']); // Update cart item
    Route::delete('/cart/{cartId}', [CartController::class, 'removeFromCart']); // Remove from cart

    // Order routes
    Route::post('/order', [OrderController::class, 'createOrder']); // Create order from cart
    // Route::get('/orders', [OrderController::class, 'getUserOrders']); // Get user orders
    Route::post('/update-order-status', [OrderController::class, 'changeOrderStatus']);
    Route::post('/update-service-status', [OrderController::class, 'changeServiceStatus']);


    Route::post('/orders', [OrderController::class, 'createWithRazorpayOrder']);
    Route::post('/orders/verify-payment', [OrderController::class, 'verifyPayment']);
    Route::get('/orders', [OrderController::class, 'userOrders']);

    Route::get('/service-requests', [OrderController::class, 'userServiceRequests']);

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
     Route::get('/categories-with-subcategories', [CategoryController::class, 'getCategoriesWithSubcategories']);

     Route::get('/merchant/dashboard', [MerchantController::class, 'dashboard']);
     Route::get('/merchant/orders', [MerchantController::class, 'orders']);
     Route::get('/merchant/orders/{orderId}', [MerchantController::class, 'orderDetails']);
     Route::get('/merchant/settlements', [MerchantController::class, 'settlementOrders']);

     Route::post('/payments/cashfree/service-requests/create-order', [PaymentController::class, 'createServiceWithCashfreeOrder']);
     Route::post('/payments/cashfree/service-requests/verify', [PaymentController::class, 'verifyServicePayment']);
     Route::post('/service-requests/create-with-razorpay', [PaymentController::class, 'createServiceWithCashfreeOrder']);
     Route::post('/service-requests/verify-payment', [PaymentController::class, 'verifyServicePayment']);

     Route::post('save-device-token', [NotificationController::class, 'saveToken']);
     Route::post('/notify-user',       [NotificationController::class, 'notifyUser']);
     Route::post('/notify-all',        [NotificationController::class, 'notifyAllUsers']);
 
     Route::get('/merchant/profile', [MerchantController::class, 'getProfile']);
     Route::post('/merchant/profile', [MerchantController::class, 'updateProfile']);
     Route::delete('/user/account', [MerchantController::class, 'deleteAccount']);

    Route::post('/payments/cashfree/create-order', [PaymentController::class, 'createWithCashfreeOrder']);
    Route::post('/payments/cashfree/verify', [PaymentController::class, 'verifyPayment']);
    Route::post('/payments/cashfree/webhook', [PaymentController::class, 'cashfreeWebhook']);
    Route::post('/orders/{orderId}/mark-delivered', [PaymentController::class, 'markDelivered']);
});

Route::post('/delvery-boy/login', [DriverAuthController::class, 'login']);
Route::post('/user/login', [MerchantController::class, 'firebaseLogin']);
Route::post('/user/verifyOtp', [MerchantController::class, 'verifyOtp']);
Route::get('/categories-with-merchants', [MerchantCategoryController::class, 'index']);
Route::get('/merchant/{merchantId}/products', [ProductController::class, 'getProductsByMerchant']);
Route::get('/products', [ProductController::class, 'getAllProducts']);
Route::get('/merchants', [MerchantController::class, 'getMerchants']);
Route::get('/merchant-category/{categoryId}/merchants', [MerchantController::class, 'getMerchantsByMerchantCategory']);
Route::get('/search-products', [ProductController::class, 'searchProducts']);
Route::get('/getCharges',[OrderController::class, 'charges']);
Route::get('/static-pages', [StaticPageController::class, 'index']);
Route::get('/static-pages/{slug}', [StaticPageController::class, 'show']);
Route::get('/ads', [AdController::class, 'index']);
Route::get('/adds', [AdController::class, 'index']);
