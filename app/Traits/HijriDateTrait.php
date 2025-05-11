<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait HijriDateTrait
{
    public function getHijriDate()
    {
        // الحصول على الوقت الحالي بتوقيت السعودية
        $now = now()->timezone('Asia/Riyadh');

        // تنسيق الوقت
        $formattedTime = $now->format('H:i:s');  // الوقت مع الثواني

        // استدعاء الـ API لتحويل التاريخ
        $response = Http::get('https://api.aladhan.com/v1/gToH', [
            'date' => $now->format('d-m-Y'),
        ]);

        // استخراج التاريخ الهجري من الاستجابة
        $hijri = $response['data']['hijri'];

        // تنسيق التاريخ الهجري مع الوقت
        return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} - الساعة {$formattedTime}";
    }

}
