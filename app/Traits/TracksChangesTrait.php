<?php

namespace App\Traits;


trait TracksChangesTrait
{
     use \App\Traits\HijriDateTrait;

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

//         if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
//             $changed[$key] = [
//                 'old' => $oldValue,
//                 'new' => $newValue,
//             ];

//             if (str_ends_with($key, '_id')) {
//                 $relation = str_replace('_id', '', $key);

//                 if (method_exists($this, $relation)) {
//                     try {
//                         $relatedModel = $this->$relation()->getRelated();

//                         $oldModel = $relatedModel->find($oldValue);
//                         $newModel = $relatedModel->find($newValue);

//                         $oldName = optional($oldModel)->name ?? optional($oldModel)->title ?? null;
//                         $newName = optional($newModel)->name ?? optional($newModel)->title ?? null;

//                         if ($oldName != $newName) {
//                             $changed[$relation . '_name'] = [
//                                 'old' => $oldName,
//                                 'new' => $newName,
//                             ];
//                         }
//                     } catch (\Throwable $e) {
//                     }
//                 }
//             }
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

            if (in_array($key, $alwaysTrack)) {
                $oldValue = $this->getOldValueForKey($key);
            }

            if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                if (str_ends_with($key, '_id')) {
                    $relation = str_replace('_id', '', $key);

                    if (method_exists($this, $relation)) {
                        try {
                            $relatedModel = $this->$relation()->getRelated();

                            $oldModel = $relatedModel->find($oldValue);
                            $newModel = $relatedModel->find($newValue);

                            $oldName = optional($oldModel)->name ?? optional($oldModel)->title ?? null;
                            $newName = optional($newModel)->name ?? optional($newModel)->title ?? null;

                            if ($oldName != $newName) {
                                $changed[$relation . '_name'] = [
                                    'old' => $oldName,
                                    'new' => $newName,
                                ];
                            }
                        } catch (\Throwable $e) {

                        }
                    }
                }
            }
        }

        if (!empty($changed)) {
            $previousChanged = $this->changed_data ?? [];

            $changed['creationDate'] = [
                'old' => $previousChanged['creationDate']['new'] ?? $this->creationDate,
                'new' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            ];

            $changed['creationDateHijri'] = [
                'old' => $previousChanged['creationDateHijri']['new'] ?? $this->creationDateHijri,
                'new' => $this->getHijriDate(),
            ];
        }

        return $changed;
    }
protected function getOldValueForKey(string $key)
{
    // نحاول نجيب آخر تعديل سابق من الـ changed_data
    $lastChanges = $this->changed_data ?? [];

    if (isset($lastChanges[$key]['new'])) {
        return $lastChanges[$key]['new'];
    }

    // fallback: القيمة الأصلية الحالية في الموديل
    return $this->$key ?? null;
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
