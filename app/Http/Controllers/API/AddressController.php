<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // Ensure the merchant is authenticated
    }

    // Get all addresses for the authenticated merchant
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses;
        return response()->json($addresses);
    }

    // Store a new address for the authenticated merchant
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_primary' => 'boolean'
        ]);

        // Set merchant_id explicitly
        $validated['merchant_id'] = $request->user()->id;

        $address = $request->user()->addresses()->create($validated);

        return response()->json($address, 201);
    }

    // Update an existing address
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_primary' => 'boolean'
        ]);

        $address = $request->user()->addresses()->findOrFail($id);
        $address->update($validated);

        return response()->json($address);
    }

    // Delete an address
    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }

    // Mark address as primary
    public function setPrimary(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->is_primary = true;
        $address->save();

        // Optionally, set other addresses to not primary
        $request->user()->addresses()->where('id', '!=', $id)->update(['is_primary' => false]);

        return response()->json($address);
    }
}
