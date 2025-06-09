<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class FlightRequest extends FormRequest
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
            // 'DateTimeTrip' =>'nullable|date_format:Y-m-d H:i:s',
            // 'DateTimeTripHijri'=>'nullable|string',
            'quantity'=>'required|string',
            // 'class'=>'required|string',
            // 'seatNum'=>'required|integer',
            'direction'=>'required|string',
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
