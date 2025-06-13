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
        'campaign_id'=>'nullable|exists:campaigns,id',
        'office_id'=>'required|exists:offices,id',
        'group_id'=>'required|exists:groups,id',
        'worker_id'=>'nullable|exists:workers,id',
        'payment_method_type_id'=>'required|exists:payment_method_types,id',

        'discount'=>'nullable|numeric|min:0|max:99999.99',
        'tax'=>'nullable|numeric|min:0|max:99999.99',
        'paidAmount'=>'nullable|numeric|min:0|max:99999.99',
        'reason' =>'nullable|string',
        'invoiceStatus' =>'nullable|in:pending,approved,rejected,completed,absence',
        'paymentStatus'=>'nullable|in:pending,paid,refunded',
        'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri'=>'nullable|string',
        // 'pilgrims' => 'nullable|array',
        // 'pilgrims.*.id' => 'required|exists:pilgrims,id',
        // 'pilgrims.*.seatNumber' => 'required|array|min:1', // ✅ السماح بأكثر من مقعد
        // 'pilgrims.*.seatNumber.*' => 'required|string',
        // 'pilgrims.*.type' => 'nullable|string',
        // 'pilgrims.*.position' => 'nullable|string',

        'pilgrims' => 'nullable|array',
        'pilgrims.*.idNum' => 'nullable|string|exists:pilgrims,idNum', // 🔹 جعل "idNum" قابلة للتحقق
        'pilgrims.*.name' => 'required_if:pilgrims.*.idNum,null|string|max:255', // 🔹 مطلوب فقط إذا لم يكن المعتمر مسجلًا مسبقًا
        'pilgrims.*.phoNum' => 'nullable|string|max:20',
        'pilgrims.*.nationality' => 'required_if:pilgrims.*.idNum,null|string|max:50',
        'pilgrims.*.gender' => 'required_if:pilgrims.*.idNum,null|in:male,female,child',
        'pilgrims.*.seatNumber' => 'required|array|min:1',
        'pilgrims.*.seatNumber.*' => 'required|string',
        'pilgrims.*.type' => 'nullable|string',
        'pilgrims.*.position' => 'nullable|string',

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
