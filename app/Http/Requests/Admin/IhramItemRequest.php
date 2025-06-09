<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class IhramItemRequest extends FormRequest
{
  
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'service_id' =>'required|exists:services,id',
            'name'=>'required|string',
            'size' =>'nullable|in:child,adult',
            'description'=>'nullable|string',
            'status' => 'nullable|in:active,notActive',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'creationDateHijri'=>'nullable|string',
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
