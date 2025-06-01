<?php

namespace App\Traits;

use App\Models\Bus;
use App\Models\Hotel;
use App\Models\Flight;

trait HasMorphMapTrait
{
    protected array $morphMap = [
        'bus'     => Bus::class,
        'flight'  => Flight::class,
        'hotel'   => Hotel::class,
        // 'product' => \App\Models\Product::class,
    ];

    public function getMorphMap(): array
    {
        return $this->morphMap;
    }

    public function getMorphClass(string $type): string
    {
        return $this->morphMap[$type] ?? $type;
    }
}
