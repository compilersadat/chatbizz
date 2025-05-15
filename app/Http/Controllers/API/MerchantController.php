<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;


class MerchantController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        $otp = rand(100000, 999999);
        $user = Merchant::updateOrCreate(
            ['mobile' => $request->mobile],
            ['otp' => $otp],
        );

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
