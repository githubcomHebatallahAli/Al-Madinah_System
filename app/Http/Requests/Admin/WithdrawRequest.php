<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
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
            'withdrawnAmount' => 'nullable|numeric|min:0|max:99999.99',
            'remainingAmount' => 'nullable|numeric|min:0|max:99999.99',
            'description' =>'required|string',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            // 'creationDateHijri'=>'nullable|string',
        ];
    }
}
