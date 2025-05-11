<?php

namespace App\Traits;


trait TracksChangesTrait
{
    public function getChangedData(array $oldData, array $newData): array
{
    $ignoredKeys = ['updated_at', 'creationDate', 'creationDateHijri'];
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
