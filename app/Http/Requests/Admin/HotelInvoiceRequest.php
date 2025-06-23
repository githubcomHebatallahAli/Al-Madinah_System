<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class HotelInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
        'main_pilgrim_id'=>'nullable|exists:pilgrims,id',
        'bus_invoice_id' => 'nullable|sometimes|exists:bus_invoices,id',
        'trip_id'=>'nullable|exists:trips,id',
        'hotel_id'=>'required|exists:hotels,id',
        'payment_method_type_id'=>'nullable|exists:payment_method_types,id',
        'checkInDate'=>'required|string',
        'checkInDateHijri'=>'nullable|date_format:Y-m-d H:i:s',
        'checkOutDate'=>'nullable|string',
        'checkOutDateHijri'=>'nullable|date_format:Y-m-d H:i:s',
        'bookingSource'=>'nullable|in:MeccaCash,MeccaDelegate,office,otherOffice',
        'roomNum'=>'nullable|integer',
        'need'=>'nullable|in:family,single',
        'sleep'=>'nullable|in:bed,room',
        'numDay'=>'required|integer',
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
        'pilgrims.*.name' => 'required_if:pilgrims.*.idNum,!=,exists:pilgrims,idNum|nullable|string',
        'pilgrims.*.nationality' => 'required_if:pilgrims.*.idNum,!=,exists:pilgrims,idNum|nullable|string',
        'pilgrims.*.gender' => 'required_if:pilgrims.*.idNum,!=,exists:pilgrims,idNum|nullable|in:male,female,child',
        'pilgrims.*.phoNum' => 'nullable|string'

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
