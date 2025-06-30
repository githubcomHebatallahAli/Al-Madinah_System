<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class MainInvoiceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
        'bus_trip_id'=>'nullable|exists:bus_trips,id',
        'main_pilgrim_id'=>'nullable|exists:pilgrims,id',
        'campaign_id'=>'nullable|exists:campaigns,id',
        'office_id'=>'nullable|exists:offices,id',
        'group_id'=>'nullable|exists:groups,id',
        'worker_id'=>'nullable|exists:workers,id',
        'payment_method_type_id'=>'nullable|exists:payment_method_types,id',

        'trip_id'=>'nullable|exists:trips,id',
        'hotel_id'=>'nullable|exists:hotels,id',
        'checkInDate'=>'nullable|date_format:Y-m-d H:i',
        'checkInDateHijri'=>'nullable|string',
        'checkOutDate'=>'nullable|date_format:Y-m-d H:i',
        'checkOutDateHijri'=>'nullable|string',
        'bookingSource'=>'nullable|in:MeccaCash,MeccaDelegate,office,otherOffice',
        'roomNum'=>'nullable|string',
        'need'=>'nullable|in:family,single',
        'sleep'=>'nullable|in:bed,room',
        'numDay'=>'nullable|integer',
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
        'pilgrims.*.seatNumber' => 'nullable|array|min:1',
        'pilgrims.*.seatNumber.*' => 'nullable|string',

        'ihramSupplies' => 'nullable|array',
        'ihramSupplies.*.id' => 'nullable|exists:ihram_supplies,id',
        'ihramSupplies.*.quantity' => 'nullable|integer|min:1',

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
