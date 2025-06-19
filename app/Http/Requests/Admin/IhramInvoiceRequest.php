<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class IhramInvoiceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
        'main_pilgrim_id'=>'nullable|exists:pilgrims,id',
        'bus_invoice_id' => 'nullable|sometimes|exists:bus_invoices,id',
        'payment_method_type_id'=>'nullable|exists:payment_method_types,id',
        'description'=>'nullable|string',

        'discount'=>'nullable|numeric|min:0|max:99999.99',
        'tax'=>'nullable|numeric|min:0|max:99999.99',
        'paidAmount'=>'nullable|numeric|min:0|max:99999.99',
        'reason' =>'nullable|string',
        'invoiceStatus' =>'nullable|in:pending,approved,rejected,completed,absence',
        'paymentStatus'=>'nullable|in:pending,paid,refunded',
        'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri'=>'nullable|string',


        'pilgrims' => 'nullable|array',
        'pilgrims.*.idNum' => 'nullable|string',
        'pilgrims.*.name' => 'required_if:pilgrims.*.idNum,!=,exists:pilgrims,idNum|nullable|string',
        'pilgrims.*.nationality' => 'required_if:pilgrims.*.idNum,!=,exists:pilgrims,idNum|nullable|string',
        'pilgrims.*.gender' => 'required_if:pilgrims.*.idNum,!=,exists:pilgrims,idNum|nullable|in:male,female,child',
        'pilgrims.*.phoNum' => 'nullable|string',

        'ihramSupplies' => 'required|array',
        'ihramSupplies.*.id' => 'required|exists:ihram_supplies,id',
        'ihramSupplies.*.quantity' => 'required|integer|min:1',
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
