<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BusInvoiceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
        'main_pilgrim_id'=>'nullable|exists:pilgrims,id',
        'bus_id' =>'required|exists:buses,id',
        'trip_id'=>'required|exists:trips,id',
        'campaign_id'=>'required|exists:campaigns,id',
        'office_id'=>'required|exists:offices,id',
        'group_id'=>'required|exists:groups,id',
        'bus_driver_id'=>'required|exists:bus_drivers,id',
        'worker_id'=>'required|exists:workers,id',
        'payment_method_type_id'=>'required|exists:payment_method_types,id',
        'travelDate'=>'nullable|date_format:Y-m-d H:i:s',
        'travelDateHijri'=>'nullable|string',
        'discount'=>'nullable|numeric|min:0|max:99999.99',
        'tax'=>'nullable|numeric|min:0|max:99999.99',
        'paidAmount'=>'required|numeric|min:0|max:99999.99',
        'bookedSeats'=>'nullable|integer',
        'reason' =>'nullable|string',
        'invoiceStatus' =>'nullable|in:pending,approved,rejected,completed,absence',
        'paymentStatus'=>'nullable|in:pending,paid,refunded',
        'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri'=>'nullable|string',
        'pilgrims' => 'nullable|array',
        'pilgrims.*.id' => 'required|exists:pilgrims,id',
        'pilgrims.*.name' => 'required|string|min:2|max:255',
        'pilgrims.*.seatNumber' => 'required|string',
        'pilgrims.*.type' => 'nullable|string',
        'pilgrims.*.position' => 'nullable|string',
        'pilgrims.*.seatPrice' => 'required|numeric|min:0',
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
