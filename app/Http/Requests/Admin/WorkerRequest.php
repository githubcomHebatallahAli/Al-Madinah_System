<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WorkerRequest extends FormRequest
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
            'admin_id' =>'nullable|exists:admins,id',
            'title_id' => 'required|exists:titles,id',
            'store_id' => 'nullable|exists:stores,id',
            'status' => 'nullable|in:active,notActive',
            'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
            'creationDateHijri'=>'nullable|string',
            'name' =>'required|string',
            'personPhoNum' =>'required|string',
            'branchPhoNum' =>'nullable|string',
            'idNum' =>'required|integer',
            'salary' =>'required|numeric|regex:/^\d{1,5}(\.\d{1,2})?$/',
            'cv' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg',
        ];
    }
}
