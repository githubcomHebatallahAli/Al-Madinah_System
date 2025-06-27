<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

trait HijriDateTrait
{
    //   public function getHijriDate()
    // {
    //     $now = now()->timezone('Asia/Riyadh');

    //     $response = Http::get('https://api.aladhan.com/v1/gToH', [
    //         'date' => $now->format('d-m-Y'),
    //     ]);

    //     $hijri = $response['data']['hijri'];

    //     return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} - {$now->format('H:i:s')}";
    // }




    public function getHijriDate(?string $gregorianDate = null, bool $includeSeconds = false)
{
    try {
        // 1) parse التاريخ مع المنطقة مباشرة
        $date = $gregorianDate
            ? Carbon::parse($gregorianDate, 'Asia/Riyadh')
            : now('Asia/Riyadh');

        // 2) نرسل التاريخ للمحوّل
        $response = Http::retry(3, 100)
                        ->get('https://api.aladhan.com/v1/gToH', [
                            'date' => $date->format('d-m-Y'),
                        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Hijri date');
        }

        $hijri   = $response->json()['data']['hijri'];
        $timeFmt = $includeSeconds ? 'H:i:s' : 'H:i';

        return sprintf(
            '%s %s %s %s - %s',
            $hijri['weekday']['ar'],
            $hijri['day'],
            $hijri['month']['ar'],
            $hijri['year'],
            $date->format($timeFmt)
        );
    }
    catch (\Throwable $e) {
        Log::error('Hijri conversion failed: '.$e->getMessage());

        // fallback للطابع الميلادي في نفس المنطقة
        $fmt = $includeSeconds ? 'Y-m-d H:i:s' : 'Y-m-d H:i';
        return $gregorianDate
            ? Carbon::parse($gregorianDate, 'Asia/Riyadh')->format($fmt)
            : now('Asia/Riyadh')->format($fmt);
    }
}




}
