<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleRequest extends FormRequest
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
            'name' =>'required|string',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'creationDateHijri'=>'nullable|string',
            'status' => 'nullable|in:active,notActive',
            'guardName'=> 'nullable|in:admin,worker',
            // 'added_by' => [
            //     'nullable',
            //     'integer',
            //     function ($attribute, $value, $fail) {
            //         $type = $this->input('added_by_type');

            //         if ($type === 'App\Models\Admin' && !\App\Models\Admin::where('id', $value)->exists()) {
            //             $fail('المُضيف غير موجود كـ Admin.');
            //         }

            //         if ($type === 'App\Models\Worker' && !\App\Models\Worker::where('id', $value)->exists()) {
            //             $fail('المُضيف غير موجود كـ Worker.');
            //         }
            //     },
            // ],
            // 'added_by_type' => [
            //     'nullable',
            //     'string',
            //     Rule::in(['App\Models\Admin', 'App\Models\Worker']),
            // ],
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
