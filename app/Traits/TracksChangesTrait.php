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

        // تتبع دائم أو تغير فعلي
        if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
            $changed[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];

            // لو المفتاح عبارة عن علاقة ID (مثل city_id)
            if (str_ends_with($key, '_id')) {
                $relation = str_replace('_id', '', $key); // city

                // لو العلاقة معرفة في الموديل
                if (method_exists($this, $relation)) {
                    try {
                        $relatedModel = $this->$relation()->getRelated();

                        $oldModel = $relatedModel->find($oldValue);
                        $newModel = $relatedModel->find($newValue);

                        // جلب الاسم المناسب
                        $oldName = optional($oldModel)->name ?? optional($oldModel)->title ?? null;
                        $newName = optional($newModel)->name ?? optional($newModel)->title ?? null;

                        // فقط لو الاسم تغير
                        if ($oldName != $newName) {
                            $changed[$relation . '_name'] = [
                                'old' => $oldName,
                                'new' => $newName,
                            ];
                        }
                    } catch (\Throwable $e) {
                        // تجاهل الخطأ إذا العلاقة غير موجودة أو غير معرفة
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
