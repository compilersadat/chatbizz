<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\Models\MerchantProduct;

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
                    ['mobile' => $phoneNumber, 'name' => $phoneNumber, 'status' => 0]
                );
                $message = 'Registration successful (new user).';
            }
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
    
    public function getProfile()
    {
        $merchant = $request->user(); // Assumes the merchant is authenticated
        return response()->json(['data' => $merchant]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
        ]);

        $merchant = $request->user();

        $merchant->update([
            'name' => $request->name,
            'mobile' => $request->mobile,
        ]);

        return response()->json(['message' => 'Profile updated successfully.', 'data' => $merchant]);
    }

}
