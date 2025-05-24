<?php

namespace App\Traits;

trait TracksChangesTrait
{
//     public function getChangedData(array $oldData, array $newData): array
// {
//     $ignoredKeys = [
//         'updated_at',
//         'updated_by',
//         'updated_by_type',
//         'changed_data'
//     ];

//     $changed = [];

//     foreach ($newData as $key => $newValue) {
//         if (in_array($key, $ignoredKeys)) {
//             continue;
//         }

//         if (array_key_exists($key, $oldData) && $oldData[$key] != $newValue) {
//             $changed[$key] = [
//                 'old' => $oldData[$key],
//                 'new' => $newValue,
//             ];
//         }
//     }

//     return $changed;
// }

public function getChangedData(array $oldData, array $newData): array
{
    $alwaysTrack = ['creationDate', 'creationDateHijri'];

    $ignoredKeys = [
        'updated_at',
        'updated_by',
        'updated_by_type',
        'changed_data'
    ];

    $changed = [];

    foreach ($newData as $key => $newValue) {
        if (in_array($key, $ignoredKeys)) {
            continue;
        }

        $oldValue = $oldData[$key] ?? null;

        // تتبع دائم للحقول المطلوبة
        if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
            $changed[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }
    }

    return $changed;
}


    public function hasRealChanges(): bool
    {
        if (!isset($this->added_by) || !isset($this->updated_by)) {
            return false;
        }

        if (!empty($this->changed_data)) {
            return true;
        }

        return $this->updated_by != $this->added_by ||
               $this->updated_by_type != $this->added_by_type;
    }
}
