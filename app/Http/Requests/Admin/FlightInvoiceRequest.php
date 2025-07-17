<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class FlightInvoiceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
        'main_pilgrim_id'=>'nullable|exists:pilgrims,id',
        'flight_id'=>'required|exists:flights,id',
        'trip_id'=>'nullable|exists:trips,id',
        'hotel_id'=>'nullable|exists:hotels,id',
        'payment_method_type_id'=>'nullable|exists:payment_method_types,id',
        'description'=>'nullable|string',

        'discount'=>'nullable|numeric|min:0|max:99999.99',
        'tax'=>'nullable|numeric|min:0|max:99999.99',
        'paidAmount'=>'nullable|numeric|min:0|max:99999.99',
        'reason' =>'nullable|string',
        'invoiceStatus' =>'nullable|in:pending,approved,rejected,completed,absence',
        'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri'=>'nullable|string',

        'pilgrims' => 'nullable|array',
        'pilgrims.*.idNum' => 'required|string',
        'pilgrims.*.phoNum' => 'nullable|string',

        'pilgrims.*.name' => 'required_without:pilgrims.*.idNum|string|max:255',
        'pilgrims.*.nationality' => 'required_without:pilgrims.*.idNum|string|max:50',
        'pilgrims.*.gender' => 'required_without:pilgrims.*.idNum|in:male,female,child',
        'pilgrims.*.seatNumber' => 'required|array|min:1',
        'pilgrims.*.seatNumber.*' => 'required|string',
        ];
    }

        public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
    }
}
