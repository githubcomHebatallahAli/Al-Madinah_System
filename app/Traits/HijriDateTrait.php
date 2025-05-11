<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait HijriDateTrait
{
    // دالة للحصول على التاريخ الهجري
    public function getHijriDate()
    {
        // الحصول على الوقت الحالي بتوقيت السعودية
        $now = now()->timezone('Asia/Riyadh')->format('d-m-Y');

        // استدعاء API لتحويل التاريخ
        $response = Http::get('https://api.aladhan.com/v1/gToH', [
            'date' => $now,
        ]);

        // استخراج التاريخ الهجري من الاستجابة
        $hijri = $response['data']['hijri'];

        // تنسيق التاريخ الهجري
        return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']}";
    }
}
