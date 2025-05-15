<?php

namespace App\Filament\Resources\RiderResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'title' => 'required|string',
			'rimg' => 'required|string',
			'status' => 'required|integer',
			'rstatus' => 'required|integer',
			'rate' => 'required|numeric',
			'lcode' => 'required|string',
			'full_address' => 'required|string',
			'pincode' => 'required|string',
			'landmark' => 'required|string',
			'bank_name' => 'required|string',
			'ifsc' => 'required|string',
			'receipt_name' => 'required|string',
			'acc_number' => 'required|string',
			'upi_id' => 'required|string',
			'email' => 'required|string',
			'password' => 'required|string',
			'mobile' => 'required|string',
			'accept' => 'required|integer',
			'reject' => 'required|integer',
			'complete' => 'required|integer',
			'dzone' => 'required',
			'vehiid' => 'required',
			'adhar_id' => 'required|string'
		];
    }
}
