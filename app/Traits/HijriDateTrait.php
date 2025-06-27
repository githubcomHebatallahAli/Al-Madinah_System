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
            $date = $gregorianDate
                ? Carbon::parse($gregorianDate)->timezone('Asia/Riyadh')
                : now()->timezone('Asia/Riyadh');

            $response = Http::retry(3, 100)->get('https://api.aladhan.com/v1/gToH', [
                'date' => $date->format('d-m-Y'),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch Hijri date from API');
            }

            $hijri = $response->json()['data']['hijri'];

            $timeFormat = $includeSeconds ? 'H:i:s' : 'H:i';

            return sprintf(
                '%s %s %s %s - %s',
                $hijri['weekday']['ar'],
                $hijri['day'],
                $hijri['month']['ar'],
                $hijri['year'],
                $date->format($timeFormat)
            );

        } catch (\Exception $e) {
            Log::error('Hijri date conversion failed: '.$e->getMessage());

            $timeFormat = $includeSeconds ? 'Y-m-d H:i:s' : 'Y-m-d H:i';

            return $gregorianDate
                ? Carbon::parse($gregorianDate)->timezone('Asia/Riyadh')->format($timeFormat)
                : now()->timezone('Asia/Riyadh')->format($timeFormat);
        }
    }




}
