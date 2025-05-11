<?php

namespace App\Traits;


trait TracksChangesTrait
{
    public function getChangedData(array $oldData, array $newData): array
    {
        $changed = [];

        foreach ($newData as $key => $newValue) {
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
