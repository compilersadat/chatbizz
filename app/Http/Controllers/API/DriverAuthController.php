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
        COUNT(CASE WHEN status = 'assigned' THEN 1 END) as pendingCount,
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

    public function driverOrders(Request $request)
    {
        $driver = $request->user();
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
    
        $query = Order::with([
            'orderItems.merchantProduct.product',
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
    Order::where('id',$request->order_id)->update(['delivery_partner_id' => $driver->id, 'status' => 'assigned']);
    return response()->json(['message' => 'Order updated.']);

}

public function AssignDriverToService(Request $request)
{
    $driver = $request->user();
    ServiceRequest::where('id',$request->service_id)->update(['delivery_partner_id' => $driver->id, 'status' => 'accepted']);
    return response()->json(['message' => 'Order updated.']);
}


public function updateDriverProfileStatus(Request $request)
    {
        Rider::where('id', $request->user()->id)
          ->update(['rstatus' => DB::raw('IF(rstatus = 1, 0, 1)')]);

          return response()->json(['message' => 'Status Updated']);
    }
}
