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








}
