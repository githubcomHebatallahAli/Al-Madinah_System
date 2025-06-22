<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

trait HijriDateTrait
{
      public function getHijriDate()
    {
        $now = now()->timezone('Asia/Riyadh');

        $response = Http::get('https://api.aladhan.com/v1/gToH', [
            'date' => $now->format('d-m-Y'),
        ]);

        $hijri = $response['data']['hijri'];

        return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} - {$now->format('H:i:s')}";
    }

//     public function getHijriDate(?string $gregorianInput = null)
// {
//     try {
//         $date = $gregorianInput
//             ? \Carbon\Carbon::parse($gregorianInput)->timezone('Asia/Riyadh')
//             : now()->timezone('Asia/Riyadh');

//         // أرسل فقط اليوم والشهر والسنة للـ API
//         $response = Http::get('https://api.aladhan.com/v1/gToH', [
//             'date' => $date->format('d-m-Y'),
//         ]);

//         if (!$response->ok()) {
//             return response()->json(['message' => 'فشل في جلب التاريخ الهجري'], 500);
//         }

//         $hijri = $response['data']['hijri'];

//         // ندمج التاريخ الهجري من الـ API مع الوقت الأصلي من الميلادي
//         return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} - {$date->format('H:i:s')}";
//     } catch (\Exception $e) {
//         Log::error('getHijriDate error: ' . $e->getMessage());
//         return response()->json([
//             'message' => 'حدث خطأ أثناء تحويل التاريخ: ' . mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8')
//         ], 500);
//     }
// }









}
