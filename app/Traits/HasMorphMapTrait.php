<?php

namespace App\Traits;

use App\Models\Bus;

trait HasMorphMapTrait
{
    protected array $morphMap = [
        'bus'     => Bus::class,
        // 'hotel'   => \App\Models\Hotel::class,
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
