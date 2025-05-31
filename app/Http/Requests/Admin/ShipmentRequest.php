<?php

namespace App\Http\Requests\Admin;

use App\Rules\ValidMorphItemRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShipmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
    $items = $this->input('items', []);

    return [
        'supplier_id' => 'nullable|exists:suppliers,id',
        'service_id' => 'required|exists:services,id',
        'company_id' => 'nullable|exists:companies,id',
        'status' => 'nullable|in:active,notActive',
        'creationDate' => 'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri' => 'nullable|string',
        'description' => 'nullable|string',
        'totalPrice' => 'nullable|numeric|min:0|max:99999.99',

        'items' => 'required|array|min:1',
        'items.*.item_id' => [
            'required',
            'integer',
            new ValidMorphItemRule($items),
        ],
        'items.*.item_type' => 'required|string|in:bus,hotel,product',
        'items.*.quantity' => 'required|numeric|min:1',
        'items.*.unitPrice' => 'required|numeric|min:0',
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
