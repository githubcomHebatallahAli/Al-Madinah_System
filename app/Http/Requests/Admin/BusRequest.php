<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BusRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
         return [
        'service_id' => 'required|exists:services,id',
        'status' => 'nullable|in:active,notActive',
        'creationDate' => 'nullable|date_format:Y-m-d H:i:s',
        'creationDateHijri' => 'nullable|string',
        'busNum' => 'required|string',
        'busModel' => 'required|string',
        'plateNum' => 'required|string',
        'seatNum' => 'required|integer|min:1|max:100',
        'seatMap' => [
            'sometimes',
            'array',
            function ($attribute, $value, $fail) {
                if (!isset($this->seatNum)) {
                    $fail("يجب تحديد عدد المقاعد أولاً");
                    return;
                }

                if (count($value) !== $this->seatNum) {
                    $fail("عدد المقاعد في seatMap لا يتطابق مع seatNum");
                }

                $seatNumbers = [];
                foreach ($value as $index => $seat) {
                    $requiredFields = ['seatNumber', 'type', 'status', 'row', 'column', 'position'];
                    foreach ($requiredFields as $field) {
                        if (!array_key_exists($field, $seat)) {
                            $fail("المقعد رقم " . ($index+1) . " يفتقد للحقل: {$field}");
                        }
                    }

                    if (!preg_match('/^[A-Za-z]{1,2}\d+$/', $seat['seatNumber'] ?? '')) {
                        $fail("تنسيق رقم المقعد غير صالح: " . ($seat['seatNumber'] ?? ''));
                    }

                    if (!in_array($seat['type'] ?? null, ['window', 'aisle', 'rearCouch'])) {
                        $fail("نوع المقعد غير صالح في المقعد: " . ($seat['seatNumber'] ?? $index+1));
                    }

                    if (!in_array($seat['status'] ?? null, ['available', 'booked', 'reserved', 'maintenance'])) {
                        $fail("حالة المقعد غير صالحة في المقعد: " . ($seat['seatNumber'] ?? $index+1));
                    }

                    if (!in_array($seat['position'] ?? null, ['left', 'right', 'center'])) {
                        $fail("موقع المقعد غير صالح في المقعد: " . ($seat['seatNumber'] ?? $index+1));
                    }

                    $seatNumbers[] = $seat['seatNumber'];
                }

                if (count($seatNumbers) !== count(array_unique($seatNumbers))) {
                    $fail("يوجد أرقام مقاعد مكررة");
                }
            }
        ],
        'quantity' => 'required|string',
        'sellingPrice' => 'required|numeric|min:0|max:99999.99',
        'purchesPrice' => 'required|numeric|min:0|max:99999.99',
    ];
    }
}
