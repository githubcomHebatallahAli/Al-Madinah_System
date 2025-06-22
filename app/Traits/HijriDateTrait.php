<?php

namespace App\Traits;

use Carbon\Carbon;
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

    //     public function getHijriDate($date = null)
    // {
    //     $date = $date
    //         ? Carbon::parse($date)->timezone('Asia/Riyadh')
    //         : now()->timezone('Asia/Riyadh');

    //     $response = Http::get('https://api.aladhan.com/v1/gToH', [
    //         'date' => $date->format('d-m-Y'),
    //     ]);

    //     if ($response->ok() && isset($response['data']['hijri'])) {
    //         $hijri = $response['data']['hijri'];

    //         return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} - {$date->format('H:i:s')}";
    //     }

    //     return null;
    // }

}
