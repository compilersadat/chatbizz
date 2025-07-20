<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Models\User;
use App\Helpers\FcmHelper; // <-- Add this

class NotificationController extends Controller
{
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
            ['user_id' => $user->id],
            ['device_token' => $request->device_token]
        );
        return response()->json(['success' => true, 'message' => 'Token saved successfully']);
    }

    // 2. Notify a particular user
    public function notifyUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title'   => 'required|string',
            'body'    => 'required|string',
        ]);
        $user = User::find($request->user_id);
        $token = optional($user->deviceToken)->device_token;
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'User token not found'], 404);
        }
        FcmHelper::send($token, $request->title, $request->body, $request->data ?? []);
        return response()->json(['success' => true, 'message' => 'Notification sent']);
    }

    // 3. Notify all users (broadcast)
    public function notifyAllUsers(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
        ]);
        $tokens = DeviceToken::pluck('device_token')->toArray();
        foreach ($tokens as $token) {
            FcmHelper::send($token, $request->title, $request->body, $request->data ?? []);
        }
        return response()->json(['success' => true, 'message' => 'Notifications sent to all users']);
    }
}
