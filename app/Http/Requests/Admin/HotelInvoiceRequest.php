<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HotelInvoiceRequest extends FormRequest
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
        'main_pilgrim_id'=>'nullable|exists:pilgrims,id',
        'campaign_id'=>'nullable|exists:campaigns,id',
        'trip_id'=>'required|exists:trips,id',
        'hotel_id'=>'required|exists:hotels,id',
        'payment_method_type_id'=>'required|exists:payment_method_types,id',
        'checkInDate'=>'nullable|string',
        'checkInDateHijri'=>'nullable|date_format:Y-m-d H:i:s',
        'checkOutDate'=>'nullable|string',
        'checkOutDateHijri'=>'nullable|date_format:Y-m-d H:i:s',
        'bookingSource'=>'nullable|in:MeccaCash, MeccaDelegate,office,otherOffice',
        'roomNum'=>'nullable|integer',
        'need'=>'nullable|in:family,single',
        'sleep'=>'nullable|in:bed,room',
        'numDay'=>'nullable|integer',
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
'pilgrims.*.name' => 'required_without:pilgrims.*.idNum|string|max:255',
'pilgrims.*.nationality' => 'required_without:pilgrims.*.idNum|string|max:50',
'pilgrims.*.gender' => 'required_without:pilgrims.*.idNum|in:male,female,child',
'pilgrims.*.type' => 'required_with:pilgrims|in:bus,trip',

        ];
    }
}
