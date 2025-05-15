<?php

namespace App\Traits;


use Illuminate\Database\Eloquent\Relations\MorphTo;

trait HasCreatorTrait
{

    public function creator(): MorphTo
    {
        return $this->morphTo('creator', 'added_by_type', 'added_by');
    }
}
