<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;


class MerchantController extends Controller
{
    
    public function firebaseLogin(Request $request)
    {
        $idToken = $request->input('idToken');
        $firebaseCredentialsPath = env('FIREBASE_CREDENTIALS');

        $auth = (new Factory)->withServiceAccount($firebaseCredentialsPath)->createAuth();
    
        try {
            $verifiedIdToken = $auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            $phoneNumber = $verifiedIdToken->claims()->get('phone_number');
    
            $user = Merchant::firstOrCreate(
                ['mobile' => $phoneNumber],
                ['name' => $phoneNumber, 'status' => 0]
            );
    
            // Optionally generate your app's token/session here
            return response()->json(['message' => 'Login successful', 'user' => $user]);
    
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid token'], 401);
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
            $merchantsQuery->where('name', 'like', "%{$search}%")
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

}
