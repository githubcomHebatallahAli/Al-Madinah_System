<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class HotelRequest extends FormRequest
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
            'service_id' => 'required|exists:services,id',
            'company_id' => 'required|exists:companies,id',
            'status' => 'nullable|in:active,notActive',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'creationDateHijri'=>'nullable|string',
            'rentalStart'=>'nullable|date_format:Y-m-d H:i',
            'rentalStartHijri'=>'nullable|string',
            'rentalEnd'=>'nullable|date_format:Y-m-d H:i',
            'rentalEndHijri'=>'nullable|string',
            'name'=>'required|string',
            'place' => 'required|in:Mecca,Almadinah',
            'address'=>'nullable|string',
            'communication'=>'nullable|string',
            'roomType' =>'nullable|in:single,double,triple,quad,suite',
            'roomNum' => 'nullable|array',
            'roomNum.*' => 'string',
            'quantity'=>'required|string',
            'bedPrice' => 'required|numeric|min:0',
            'description'=>'nullable|string',
            'sellingPrice'=> 'required|numeric|min:0|max:99999.99',
            'purchesPrice'=> 'required|numeric|min:0|max:99999.99',
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
