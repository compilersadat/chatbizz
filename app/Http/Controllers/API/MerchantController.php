<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Merchant;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\Models\MerchantProduct;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    
    public function firebaseLogin(Request $request)
    {
        // Retrieve the ID token from the request input.
        $idToken = $request->input('idToken');

        // Get the path to Firebase credentials from environment variables.
        // Ensure 'FIREBASE_CREDENTIALS' is correctly set in your .env file
        // pointing to your service account JSON file.
        $firebaseCredentialsPath = env('FIREBASE_CREDENTIALS');

        // Check if the Firebase credentials path is set.
        if (empty($firebaseCredentialsPath)) {
            // Return an error if credentials path is not configured.
            return response()->json(['error' => 'Firebase credentials path not configured.'], 500);
        }

        try {
            // Create a Firebase Factory instance using the service account.
            $factory = (new Factory)->withServiceAccount($firebaseCredentialsPath);

            // Get the Firebase Authentication instance.
            $auth = $factory->createAuth();

            // Verify the Firebase ID token. This throws an exception if invalid.
            $verifiedIdToken = $auth->verifyIdToken($idToken);

            // Extract the Firebase UID (subject) from the verified token claims.
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            // Extract the phone number from the verified token claims.
            $phoneNumber = $verifiedIdToken->claims()->get('phone_number');

            // Initialize a message variable for the response.
            $message = '';

            // Attempt to find an existing merchant by phone number.
            $user = Merchant::where('mobile', $phoneNumber)->first();

            // Check if a user was found.
            if ($user) {
                // If user exists, it's a login for an already registered user.
                $message = 'Login successful (existing user).';
            } else {
                // If user does not exist, create a new merchant record.
                // 'name' is set to the phone number, and 'status' is 0.
                $user = Merchant::create(
                    ['mobile' => $phoneNumber, 'name' => $phoneNumber, 'status' => 0,'merchant_type' => 'none']
                );
                $message = 'Registration successful (new user).';
            }

            $this->syncMerchantToFirestore($user, false);

            $token = $user->createToken('mobile-login')->plainTextToken;
            return response()->json(['message' => $message, 'user' => $user,'token' => $token]);

        } catch (\Kreait\Firebase\Exception\Auth\InvalidToken $e) {
            return response()->json(['error' => 'Invalid or expired Firebase ID token.'], 401);
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            return response()->json(['error' => 'Firebase error: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    protected function syncMerchantToFirestore(Merchant $merchant, bool $forceUpdate = false): void
    {
        try {
            $firestore = FirebaseUserService::firestore()->database();
            $mobile = $merchant->mobile;

            if (empty($mobile)) {
                return;
            }

            $existingUser = $firestore
                ->collection('users')
                ->where('mobile', '=', $mobile)
                ->limit(1)
                ->documents();

            foreach ($existingUser as $document) {
                if ($document->exists()) {
                    if (! $forceUpdate) {
                        return;
                    }

                    $document->reference()->set([
                        'name' => $merchant->name,
                        'mobile' => $mobile,
                        'merchant_type' => $merchant->merchant_type,
                        'visible_on_chat' => (bool) $merchant->visible_on_chat,
                    ], ['merge' => true]);

                    return;
                }
            }

            $firestore
                ->collection('users')
                ->document((string) $merchant->id)
                ->set([
                    'name' => $merchant->name,
                    'mobile' => $mobile,
                    'merchant_type' => $merchant->merchant_type,
                    'visible_on_chat' => (bool) $merchant->visible_on_chat,
                ], ['merge' => true]);
        } catch (\Throwable $e) {
            Log::warning('Merchant Firestore sync failed after Firebase login', [
                'merchant_id' => $merchant->id,
                'mobile' => $merchant->mobile,
                'error' => $e->getMessage(),
            ]);
        }
    }
    

    public function sendOtp(Request $request)
{
    $request->validate([
        'mobile' => 'required|digits:10',
    ]);

    $otp = rand(100000, 999999);

    $user = Merchant::where('mobile', $request->mobile)->first();

    if (!$user) {
        // Create a new merchant with name same as mobile
        $user = Merchant::create([
            'mobile' => $request->mobile,
            'name' => $request->mobile, // name = mobile
            'otp' => $otp,
        ]);
    } else {
        // Just update the OTP
        $user->otp = $otp;
        $user->save();
    }

    // TODO: Send OTP via SMS API (for now return in response)
    return response()->json(['message' => 'OTP sent', 'otp' => $otp]);
}


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:6',
        ]);

        $user = Merchant::where('mobile', $request->mobile)
                    ->where('otp', $request->otp)
                    ->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid OTP'], 401);
        }

        $user->update(['otp' => null]); // clear OTP

        $token = $user->createToken('mobile-login')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function getMerchants(Request $request)
    {
        // Get search term and pagination parameters from the request
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10); // Default to 10 if not provided

        // Start query for merchants
        $merchantsQuery = Merchant::query();

        // If a search term is provided, filter merchants based on name or other fields
        if ($search) {
            $merchantsQuery->where('name', 'like', "%{$search}%")->where('status',1)
                           ->orWhere('mobile', 'like', "%{$search}%")
                           ->orWhere('address', 'like', "%{$search}%");
        }

        // Paginate the results
        $merchants = $merchantsQuery->paginate($perPage);

        // Return response
        return response()->json([
            'success' => true,
            'data' => $merchants
        ]);
    }

    public function addProductsToMerchant(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:tbl_product,id',
            'products.*.stock' => 'nullable|integer|min:0',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.description' => 'nullable|string'
        ]);
    
        $merchant = Merchant::findOrFail($request->user()->id);
    
        foreach ($request->products as $prod) {
            MerchantProduct::updateOrCreate(
                [
                    'merchant_id' =>  $request->user()->id,
                    'product_id' => $prod['product_id'],
                ],
                [
                    'stock' => $prod['stock'] ?? null,
                    'price' => $prod['price'] ?? null,
                    'discount' => $prod['discount'] ?? null,
                    'description' => $prod['description'] ?? null,
                ]
            );
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Products added/updated for merchant successfully.'
        ]);
    }
    
    public function getProfile(Request $request)
    {
        $merchant = $request->user(); // Assumes the merchant is authenticated
        return response()->json(['data' => $merchant]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'visible_on_chat' => 'sometimes|boolean',
        ]);

        $merchant = $request->user();

        $updateData = [
            'name' => $request->name,
            'mobile' => $request->mobile,
        ];

        if ($request->has('visible_on_chat')) {
            $updateData['visible_on_chat'] = $request->boolean('visible_on_chat');
        }

        $merchant->update($updateData);

        $merchant->refresh();
        $this->syncMerchantToFirestore($merchant, true);

        return response()->json(['message' => 'Profile updated successfully.', 'data' => $merchant]);
    }

    /**
     * Delete the authenticated merchant account and revoke API tokens.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $merchant = $request->user();

        DB::transaction(function () use ($merchant) {
            $merchant->tokens()->delete();
            $merchant->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }

    public function dashboard(Request $request)
    {
        $merchant = $request->user();

        $baseQuery = Order::where('shop_id', $merchant->id);

        $totalOrders = (clone $baseQuery)->count();
        $todayOrders = (clone $baseQuery)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $statusCounts = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $knownStatuses = [
            'pending',
            'paid',
            'assigned',
            'confirmed',
            'packed',
            'picked_up',
            'in_transit',
            'delivered',
            'rejected',
            'failed',
            'canceled',
            'cancelled',
            'refunded',
        ];

        $statusBreakdown = [];
        foreach ($knownStatuses as $status) {
            $statusBreakdown[$status] = $statusCounts[$status] ?? 0;
        }

        $deliveredQuery = (clone $baseQuery)->where('status', 'delivered');

        $totalRevenue = (clone $deliveredQuery)->sum('total_amount');

        $merchantSettledQuery = (clone $deliveredQuery)->where('merchant_payment_settled', true);
        $merchantUnsettledQuery = (clone $deliveredQuery)->where('merchant_payment_settled', false);

        $merchantSettledAmount = (clone $merchantSettledQuery)->sum('total_amount');
        $merchantUnsettledAmount = (clone $merchantUnsettledQuery)->sum('total_amount');
        $merchantSettledCount = (clone $merchantSettledQuery)->count();
        $merchantUnsettledCount = (clone $merchantUnsettledQuery)->count();

        $riderSettledQuery = (clone $deliveredQuery)->where('rider_payment_settled', true);
        $riderUnsettledQuery = (clone $deliveredQuery)->where('rider_payment_settled', false);

        $riderSettledCount = (clone $riderSettledQuery)->count();
        $riderUnsettledCount = (clone $riderUnsettledQuery)->count();
        $riderSettledAmount = (clone $riderSettledQuery)->sum('delivery_charges');
        $riderUnsettledAmount = (clone $riderUnsettledQuery)->sum('delivery_charges');

        $recentLimit = max(1, (int) $request->query('recent_limit', 5));

        $recentOrders = (clone $baseQuery)
            ->latest('created_at')
            ->take($recentLimit)
            ->get([
                'id',
                'merchant_transaction_id',
                'status',
                'total_amount',
                'created_at',
                'contact_name',
                'contact_number',
                'merchant_payment_settled',
                'rider_payment_settled',
            ])
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'merchant_transaction_id' => $order->merchant_transaction_id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'contact_name' => $order->contact_name,
                    'contact_number' => $order->contact_number,
                    'placed_at' => $order->created_at?->toDateTimeString(),
                    'merchant_payment_settled' => (bool) $order->merchant_payment_settled,
                    'rider_payment_settled' => (bool) $order->rider_payment_settled,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'orders' => $totalOrders,
                    'orders_today' => $todayOrders,
                    'revenue' => $totalRevenue,
                ],
                'status_breakdown' => $statusBreakdown,
                'settlements' => [
                    'merchant' => [
                        'settled_orders' => $merchantSettledCount,
                        'unsettled_orders' => $merchantUnsettledCount,
                        'settled_amount' => $merchantSettledAmount,
                        'unsettled_amount' => $merchantUnsettledAmount,
                    ],
                    'rider' => [
                        'settled_deliveries' => $riderSettledCount,
                        'unsettled_deliveries' => $riderUnsettledCount,
                        'settled_amount' => $riderSettledAmount,
                        'unsettled_amount' => $riderUnsettledAmount,
                    ],
                ],
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    public function orders(Request $request)
    {
        $merchant = $request->user();

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $status = $request->query('status');
        $search = $request->query('search');

        $query = Order::with([
                'orderItems:id,order_id,product_name,quantity,price',
                'deliveryPartner:id,title,mobile',
                'user:id,name,mobile',
            ])
            ->where('shop_id', $merchant->id)
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('merchant_transaction_id', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->latest('created_at');

        $orders = $query->paginate($perPage);

        $orders->setCollection(
            $orders->getCollection()->map(fn (Order $order) => $this->transformOrder($order, [
                'include_items' => false,
                'include_address' => false,
            ]))
        );

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders->items(),
            ],
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'has_more' => $orders->hasMorePages(),
            ],
            'links' => [
                'next' => $orders->nextPageUrl(),
                'prev' => $orders->previousPageUrl(),
            ],
        ]);
    }

    public function orderDetails(Request $request, int $orderId)
    {
        $merchant = $request->user();

        $order = Order::with([
                'orderItems:id,order_id,product_name,quantity,price,product_id',
                'deliveryPartner:id,title,mobile',
                'user:id,name,mobile',
                'address',
            ])
            ->where('shop_id', $merchant->id)
            ->find($orderId);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order),
        ]);
    }

    private function transformOrder(Order $order, array $options = []): array
    {
        $options = array_merge([
            'include_items' => true,
            'include_address' => true,
            'include_customer' => true,
        ], $options);

        $orderItems = $order->relationLoaded('orderItems') ? $order->orderItems : collect();

        $data = [
            'id' => $order->id,
            'merchant_transaction_id' => $order->merchant_transaction_id,
            'status' => $order->status,
            'sub_total' => (float) $order->sub_total,
            'delivery_charges' => (float) $order->delivery_charges,
            'platform_fee' => (float) $order->platform_fee,
            'total_amount' => (float) $order->total_amount,
            'merchant_payment_settled' => (bool) $order->merchant_payment_settled,
            'rider_payment_settled' => (bool) $order->rider_payment_settled,
            'contact' => [
                'name' => $order->contact_name,
                'number' => $order->contact_number,
            ],
            'items_summary' => [
                'count' => $orderItems->count(),
                'total_quantity' => $orderItems->sum('quantity'),
            ],
            'placed_at' => optional($order->created_at)->toDateTimeString(),
            'updated_at' => optional($order->updated_at)->toDateTimeString(),
        ];

        $deliveryPartner = $order->relationLoaded('deliveryPartner') ? $order->deliveryPartner : null;

        $data['delivery_partner'] = $deliveryPartner ? [
            'id' => $deliveryPartner->id,
            'name' => $deliveryPartner->name ?? null,
            'mobile' => $deliveryPartner->mobile ?? null,
        ] : null;

        if ($options['include_customer']) {
            $customer = $order->relationLoaded('user') ? $order->user : null;
            $data['customer'] = $customer ? [
                'id' => $customer->id,
                'name' => $customer->name ?? null,
                'mobile' => $customer->mobile ?? null,
            ] : null;
        }

        if ($options['include_address']) {
            $address = $order->relationLoaded('address') ? $order->address : null;
            $data['address'] = $address ? [
                'id' => $address->id,
                'line_1' => $address->address_line_1,
                'line_2' => $address->address_line_2,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
            ] : null;
        }

        if ($options['include_items']) {
            $data['items'] = $orderItems->map(function (OrderItem $item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->price,
                    'subtotal' => (float) ($item->price * $item->quantity),
                ];
            })->values();
        }

        return $data;
    }

    public function settlementOrders(Request $request)
    {
        $merchant = $request->user();

        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 100));

        $baseQuery = Order::with([
                'orderItems:id,order_id,quantity',
                'deliveryPartner:id,title,mobile',
                'user:id,name,mobile',
            ])
            ->where('shop_id', $merchant->id)
            ->where('status', 'delivered');

        $columns = [
            'id',
            'merchant_transaction_id',
            'status',
            'sub_total',
            'delivery_charges',
            'platform_fee',
            'total_amount',
            'merchant_payment_settled',
            'rider_payment_settled',
            'contact_name',
            'contact_number',
            'created_at',
            'updated_at',
            'delivery_partner_id',
            'user_id',
            'address_id',
            'shop_id',
        ];

        $settledQuery = (clone $baseQuery)->where('merchant_payment_settled', true);
        $unsettledQuery = (clone $baseQuery)->where('merchant_payment_settled', false);

        $settledOrders = (clone $settledQuery)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get($columns)
            ->map(fn (Order $order) => $this->transformOrder($order, [
                'include_items' => false,
                'include_address' => false,
                'include_customer' => false,
            ]));

        $unsettledOrders = (clone $unsettledQuery)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get($columns)
            ->map(fn (Order $order) => $this->transformOrder($order, [
                'include_items' => false,
                'include_address' => false,
                'include_customer' => false,
            ]));

        return response()->json([
            'success' => true,
            'data' => [
                'limit' => $limit,
                'settled' => [
                    'count' => (clone $settledQuery)->count(),
                    'total_amount' => (float) (clone $settledQuery)->sum('total_amount'),
                    'orders' => $settledOrders,
                ],
                'unsettled' => [
                    'count' => (clone $unsettledQuery)->count(),
                    'total_amount' => (float) (clone $unsettledQuery)->sum('total_amount'),
                    'orders' => $unsettledOrders,
                ],
            ],
        ]);
    }

}
