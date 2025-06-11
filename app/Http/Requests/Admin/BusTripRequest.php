<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BusTripRequest extends FormRequest
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
        'bus_id' =>'required|exists:buses,id',
        'trip_id'=>'required|exists:trips,id',
        'bus_driver_id'=>'required|exists:bus_drivers,id',
        'travelDate'=>'nullable|date_format:Y-m-d H:i:s',
        'travelDateHijri'=>'nullable|string',
        'creationDate' =>'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri'=>'nullable|string',
        ];
    }
}
