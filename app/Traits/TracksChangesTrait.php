<?php

namespace App\Traits;

trait TracksChangesTrait
{


// public function getChangedData(array $oldData, array $newData): array
// {
//     $alwaysTrack = ['creationDate', 'creationDateHijri'];

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

//         $oldValue = $oldData[$key] ?? null;

//         // تتبع دائم للحقول المطلوبة
//         if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
//             $changed[$key] = [
//                 'old' => $oldValue,
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

        if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
            $changed[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];

            // لو المفتاح ينتهي بـ _id، أضف المفتاح المرتبط بالاسم في مفتاح مستقل
            if (str_ends_with($key, '_id')) {
                $relationName = str_replace('_id', '', $key);
                if (method_exists($this, $relationName)) {
                    try {
                        $relatedModel = $this->$relationName()->getRelated();

                        $oldModel = $relatedModel->find($oldValue);
                        $newModel = $relatedModel->find($newValue);

                        $oldName = optional($oldModel)->name ?? optional($oldModel)->title;
                        $newName = optional($newModel)->name ?? optional($newModel)->title;

                        if ($oldName != $newName) {
                            $changed[$relationName . '_name'] = [
                                'old' => $oldName,
                                'new' => $newName,
                            ];
                        }
                    } catch (\Throwable $e) {
                        // لا تقطع التنفيذ لو حصلت مشكلة
                    }
                }
            }
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
