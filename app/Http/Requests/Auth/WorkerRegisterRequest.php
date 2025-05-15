<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class WorkerRegisterRequest extends FormRequest
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
            'email' => 'required|string|email|max:100|unique:worker_logins',
            'password' => 'required|string|confirmed|min:6',
            'role_id' => 'nullable|exists:roles,id',
            'worker_id' => 'nullable|exists:workers,id',
            'added_by' => 'nullable','exists:workers,id',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'creationDateHijri'=>'nullable|string',
        ];
    }
}
