<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BusRequest extends FormRequest
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
            'company_id' => 'required|exists:companies,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'status' => 'nullable|in:active,notActive',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'creationDateHijri'=>'nullable|string',
            'busNum'=>'required|integer',
            'busModel'=>'required|string',
            'plateNum'=>'required|string',
            'seatNum'=>'required|integer',
            'quantity'=>'required|string',
            'sellingPrice'=> 'required|numeric|min:0|max:99999.99',
            'purchesPrice'=> 'required|numeric|min:0|max:99999.99',
        ];
    }
}
