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

    // التأكد من أن الـ API رجعت بيانات بشكل صحيح
    if ($response->successful()) {
        $hijri = $response['data']['hijri'];
        // هنا تم إزالة "الساعة" والشرط "-"
        return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} {$formattedTime}";
    } else {
        return null; // يمكنك تعديل هذا بناءً على احتياجاتك
    }
}
 

}
