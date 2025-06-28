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
            COUNT(CASE WHEN o_status = 'Pending' THEN 1 END) as pendingCount,
            COUNT(CASE WHEN o_status = 'Completed' THEN 1 END) as completedCount,
            COUNT(CASE WHEN o_status = 'Cancelled' THEN 1 END) as cancelledCount,
            SUM(dcommission) as totalCommission
        ")
        ->first();


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
        $upcomingOrders =  DB::table('orders')
        ->where('delivery_status', 'pending')
        ->where('status','confirmed')
        ->whereRaw("ST_Contains(ST_GeomFromText(?), (POINT(CAST(pick_lng AS DECIMAL(10,6)), CAST(pick_lat AS DECIMAL(10,6)))))", [$zone])
        ->whereRaw("ST_Contains(ST_GeomFromText(?), POINT(CAST(drop_lng AS DECIMAL(10,6)), CAST(drop_lat AS DECIMAL(10,6))))", [$zone])
        ->get();

        return response()->json([
            'data' => [
                'pendingCount' => $orderCounts->pendingCount,
                'completedCount' => $orderCounts->completedCount,
                'cancelledCount' => $orderCounts->cancelledCount,
                'upcomingOrders' => $upcomingOrders,
                'totalEarning' => $orderCounts->totalCommission
            ]
        ]);
    }


    public function updateDriverProfileStatus(Request $request)
    {
        Rider::where('id', $request->user()->id)
          ->update(['rstatus' => DB::raw('IF(rstatus = 1, 0, 1)')]);

          return response()->json(['message' => 'Status Updated']);
    }
}
