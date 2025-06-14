<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePilgrimDataRequest extends FormRequest
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
            'pilgrims' => 'required|array',
            'pilgrims.*.idNum' => 'nullable|string',
            'pilgrims.*.name' => 'required_without:pilgrims.*.idNum|string|max:255',
            'pilgrims.*.nationality' => 'required_without:pilgrims.*.idNum|string|max:50',
            'pilgrims.*.gender' => 'required_without:pilgrims.*.idNum|in:male,female,child',
            'pilgrims.*.seatNumber' => 'required|array|min:1',
            'pilgrims.*.seatNumber.*' => 'required|string',
            'pilgrims.*.type' => 'nullable|string',
            'pilgrims.*.position' => 'nullable|string',
        ];
    }
}
