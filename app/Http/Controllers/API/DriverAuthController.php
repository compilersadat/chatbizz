<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Rider;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\ServiceRequest;
use App\Models\DeviceToken;
use App\Helpers\FcmHelper;
use App\Services\FirebaseUserService;


class DriverAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $driver = Rider::where('email', $request->email)->first();

        if (!$driver || !Hash::check($request->password, $driver->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $driver->createToken('Driver API Token')->plainTextToken;
        $data["user"] = $driver;
        
        return response()->json([
            'data' => $data,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
    public function profile(Request $request)
    {
        $driver = $request->user();
        // Fetch order counts in a single query
        $orderCounts = Order::where('delivery_partner_id', $driver->id)
        ->selectRaw("
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as pendingCount,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completedCount,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelledCount,
            SUM(CASE WHEN status = 'delivered' THEN delivery_charges ELSE 0 END) as totalCommission
        ")
        ->first();
        $serviceCounts = ServiceRequest::where('delivery_partner_id', $driver->id)
        ->selectRaw("
        COUNT(CASE WHEN status = 'accepted' THEN 1 END) as pendingCount,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completedCount,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelledCount,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as totalCommission
    ")->first();

            $zone = DB::table('zones')
            ->where('id', $driver->dzone)
            ->selectRaw('ST_AsText(coordinates) as coordinates')
            ->value('coordinates'); 

        if (!$zone) {
            return response()->json([
                'error' => 'Zone not found'
            ], 404);
        }

        // Fetch orders where both pickup & drop-off are inside the zone
        // $upcomingOrders =  DB::table('orders')
        // ->where('status', 'paid')
        // ->whereRaw("ST_Contains(ST_GeomFromText(?), (POINT(CAST(pick_lng AS DECIMAL(10,6)), CAST(pick_lat AS DECIMAL(10,6)))))", [$zone])
        // ->whereRaw("ST_Contains(ST_GeomFromText(?), POINT(CAST(drop_lng AS DECIMAL(10,6)), CAST(drop_lat AS DECIMAL(10,6))))", [$zone])
        // ->get();

        $upcomingOrders = Order::with([
            'orderItems.merchantProduct.product',

            'shop' => function($q) {
                $q->select('id','address', 'name');
            },
            'address' => function($q) {
                $q->select('id','address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country');
            },
        ])
        ->where('status', 'paid')
        ->whereRaw("ST_Contains(ST_GeomFromText(?), POINT(CAST(pick_lng AS DECIMAL(10,6)), CAST(pick_lat AS DECIMAL(10,6))))", [$zone])
        ->whereRaw("ST_Contains(ST_GeomFromText(?), POINT(CAST(drop_lng AS DECIMAL(10,6)), CAST(drop_lat AS DECIMAL(10,6))))", [$zone])
        ->get();

        $upcomingServiceRequest = ServiceRequest::whereRaw("ST_Contains(ST_GeomFromText(?), POINT(CAST(pickup_long AS DECIMAL(10,6)), CAST(pickup_lat AS DECIMAL(10,6))))", [$zone])
        ->where('status', 'pending')
        ->get();


        
        

        return response()->json([
            'data' => [
                'pendingCount' => $orderCounts->pendingCount,
                'completedCount' => $orderCounts->completedCount,
                'cancelledCount' => $orderCounts->cancelledCount,
                'upcomingOrders' => $upcomingOrders,
                'totalEarning' => $orderCounts->totalCommission,
                'upcomingServiceRequest' => $upcomingServiceRequest,
                'pendingServiceCount' => $serviceCounts->pendingCount,
                'completedServiceCount' => $serviceCounts->completedCount,
                'cancelledServiceCount' => $serviceCounts->cancelledCount,
                'totalServiceEarning' => $orderCounts->totalCommission,
            ]
        ]);
    }

     // 1. Save or update FCM device token
     public function saveToken(Request $request)
     {
         $request->validate([
             'device_token' => 'required|string'
         ]);
         $user = $request->user(); // Requires auth middleware
         if (!$user) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
         }
         DeviceToken::updateOrCreate(
             ['user_id' => $user->id , 'user_type' => 'driver'],
             ['device_token' => $request->device_token]
         );
         return response()->json(['success' => true, 'message' => 'Token saved successfully']);
     }

    public function driverOrders(Request $request)
    {
        $driver = $request->user();
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
    
        $query = Order::with([
            'shop' => function($q) {
                $q->select('id', 'address', 'name');
            },
            'address' => function($q) {
                $q->select('id', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country');
            }
        ])
        ->where('delivery_partner_id', $driver->id);
    
        if ($status) {
            $query->where('status', $status);
        }
    
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);
    
        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
                'next_page_url' => $orders->nextPageUrl(),
                'prev_page_url' => $orders->previousPageUrl(),
                'data' => $orders->items(),
            ],
        ]);
    }


    public function orderDetails(Request $request, $orderId)
{
    $order = Order::with([
        'orderItems.merchantProduct.product',
        'shop' => function($q) {
            $q->select('id', 'address', 'name', 'lat', 'lang');
        },
        'address' => function($q) {
            $q->select('id', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'latitude', 'longitude');
        },
        'deliveryPartner' => function($q) {
            $q->select('id', 'title', 'mobile', 'email', 'status', 'rstatus', 'rate', 'rimg', 'adhar_id', 'full_address', 'pincode', 'landmark', 'dzone', 'bank_name', 'ifsc', 'receipt_name', 'acc_number', 'upi_id', 'created_at', 'updated_at');
        }
    ])
    ->find($orderId);

    if (!$order) {
        return response()->json(['success' => false, 'message' => 'Order not found'], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $order,
    ]);
}


    
    public function driverServices(Request $request)
    {
        $driver = $request->user();
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
    
        $query = ServiceRequest::with([
            'user'
        ])
        ->where('delivery_partner_id', $driver->id);
    
        if ($status) {
            $query->where('status', $status);
        }
    
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);
    
        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
                'next_page_url' => $orders->nextPageUrl(),
                'prev_page_url' => $orders->previousPageUrl(),
                'data' => $orders->items(),
            ],
        ]);
    }

public function AssignDriver(Request $request)
{
    $driver = $request->user();
    $order = Order::find($request->order_id);

    $order->update([
        'delivery_partner_id' => $driver->id,
        'status' => 'assigned',
        'completion_otp' => rand(100000, 999999)
    ]);
    $deviceToken = DeviceToken::where('user_id', $order->user_id)->where('user_type','customer')->value('device_token');
    if ($deviceToken) {
        FcmHelper::send(
            $deviceToken,
            'Driver Assigned!',
            'A delivery partner has been assigned to your order #'.$order->id,
            [
                'order_id' => (string) $order->id,
                'type' => 'order_driver_assigned',
            ],
        );
    }

    return response()->json(['message' => 'Order updated.']);

}

public function AssignDriverToService(Request $request)
{
    $driver = $request->user();
    $service_request = ServiceRequest::find($request->service_id);
    $service_request->update([
        'delivery_partner_id' => $driver->id,
         'status' => 'accepted'
        ]);
    $deviceToken = DeviceToken::where('user_id', $service_request->user_id)->where('user_type','customer')->value('device_token');
    
        if ($deviceToken) {
            FcmHelper::send(
                $deviceToken,
                'Driver Assigned!',
                'A delivery partner has been assigned to your service #'.$service_request->id,
                [
                    'service_id' => (string) $service_request->id,
                    'type' => 'request_driver_assigned',
                ],
            );
        }
    return response()->json(['message' => 'Order updated.']);
}

public function updateLocation(Request $request)
{
    $request->validate([
        'order_id' => 'required',
        'driver_id' => 'required',
        'lat'      => 'required|numeric',
        'lng'      => 'required|numeric',
    ]);

    // Save/update in User Firestore
    try {
        $firestore = FirebaseUserService::firestore();
        $docRef = $firestore
            ->database()
            ->collection('orders')
            ->document($request->order_id)
            ->collection('tracking')
            ->document('driver_location');
        $docRef->set([
            'driver_id' => $request->driver_id,
            'lat' => (float) $request->lat,
            'lng' => (float) $request->lng,
            'updated_at' => now()->toIso8601String(),
        ], ['merge' => true]);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }

    return response()->json(['success' => true]);
}


public function updateDriverProfileStatus(Request $request)
    {
        Rider::where('id', $request->user()->id)
          ->update(['rstatus' => DB::raw('IF(rstatus = 1, 0, 1)')]);

          return response()->json(['message' => 'Status Updated']);
    }
}
