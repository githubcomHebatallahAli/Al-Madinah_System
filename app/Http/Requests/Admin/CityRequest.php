<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
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
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:active,notActive',
            'name' =>'required|string',
            'admin_id' =>'nullable|exists:admins,id',
        ];
    }
}
