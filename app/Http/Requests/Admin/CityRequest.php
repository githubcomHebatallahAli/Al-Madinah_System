<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
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
            'creationDateHijri'=>'nullable|string',
            'status' => 'nullable|in:active,notActive',
            'name' =>'required|string',
            'added_by' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('added_by_type');

                    if ($type === 'App\Models\Admin' && !\App\Models\Admin::where('id', $value)->exists()) {
                        $fail('المُضيف غير موجود كـ Admin.');
                    }

                    if ($type === 'App\Models\Worker' && !\App\Models\Worker::where('id', $value)->exists()) {
                        $fail('المُضيف غير موجود كـ Worker.');
                    }
                },
            ],
            'added_by_type' => [
                'nullable',
                'string',
                Rule::in(['App\Models\Admin', 'App\Models\Worker']),
            ],
        ];
    }
}
