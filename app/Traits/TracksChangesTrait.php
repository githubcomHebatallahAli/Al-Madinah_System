<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;


trait TracksChangesTrait
{
    public function getChangedData(array $oldData, array $newData): array
{
    $ignoredKeys = ['updated_at'];
    $changed = [];

    foreach ($newData as $key => $newValue) {
        if (in_array($key, $ignoredKeys)) {
            continue;
        }

        if (array_key_exists($key, $oldData) && $oldData[$key] !== $newValue) {
            $changed[$key] = [
                'old' => $oldData[$key],
                'new' => $newValue,
            ];
        }
    }

    return $changed;
}


}
