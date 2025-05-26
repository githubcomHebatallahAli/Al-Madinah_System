<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait TracksChangesTrait
{

  protected array $alwaysTrack = ['creationDate', 'creationDateHijri'];

    protected array $ignoredKeys = [
        'updated_at',
        'updated_by',
        'updated_by_type',
        'changed_data'
    ];

    public function getChangedData(array $oldData, array $newData): array
    {
        $changed = [];

        foreach ($newData as $key => $newValue) {
            if (in_array($key, $this->ignoredKeys)) {
                continue;
            }

            $oldValue = $oldData[$key] ?? null;

            // تحقق من التغيرات المباشرة
            if (in_array($key, $this->alwaysTrack) || $oldValue != $newValue) {
                $changed[$key] = ['old' => $oldValue, 'new' => $newValue];

                // إذا كان الحقل ينتهي بـ _id، حاول تلقائيًا تجيب اسم العلاقة
                if (str_ends_with($key, '_id')) {
                    $relationName = substr($key, 0, -3); // احذف _id

                    // تحقق من وجود علاقة في الموديل
                    if (method_exists($this, $relationName)) {

                        // حاول تجيب القيمة القديمة للجسم المرتبط (من oldData)
                        $oldRelatedName = $this->getRelatedNameFromOldData($oldData, $relationName);
                        $newRelatedName = $this->getRelatedNameFromNewData($newData, $relationName);

                        if ($oldRelatedName !== null || $newRelatedName !== null) {
                            $changed[$key]['old_name'] = $oldRelatedName;
                            $changed[$key]['new_name'] = $newRelatedName;
                        }
                    }
                }
            }
        }

        return $changed;
    }

    protected function getRelatedNameFromOldData(array $oldData, string $relationName)
    {
        if (isset($oldData[$relationName]) && is_array($oldData[$relationName])) {
            return $oldData[$relationName]['name'] ?? null;
        }
        return null;
    }

    protected function getRelatedNameFromNewData(array $newData, string $relationName)
    {
        if (isset($newData[$relationName]) && is_array($newData[$relationName])) {
            return $newData[$relationName]['name'] ?? null;
        }
        return null;
    }






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

//         // تتبع دائم أو تغير فعلي
//         if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
//             $changed[$key] = [
//                 'old' => $oldValue,
//                 'new' => $newValue,
//             ];

//             // لو المفتاح عبارة عن علاقة ID (مثل city_id)
//             if (str_ends_with($key, '_id')) {
//                 $relation = str_replace('_id', '', $key); // city

//                 // لو العلاقة معرفة في الموديل
//                 if (method_exists($this, $relation)) {
//                     try {
//                         $relatedModel = $this->$relation()->getRelated();

//                         $oldModel = $relatedModel->find($oldValue);
//                         $newModel = $relatedModel->find($newValue);

//                         // جلب الاسم المناسب
//                         $oldName = optional($oldModel)->name ?? optional($oldModel)->title ?? null;
//                         $newName = optional($newModel)->name ?? optional($newModel)->title ?? null;

//                         // فقط لو الاسم تغير
//                         if ($oldName != $newName) {
//                             $changed[$relation . '_name'] = [
//                                 'old' => $oldName,
//                                 'new' => $newName,
//                             ];
//                         }
//                     } catch (\Throwable $e) {
//                         // تجاهل الخطأ إذا العلاقة غير موجودة أو غير معرفة
//                     }
//                 }
//             }
//         }
//     }

//     return $changed;
// }


protected function getOriginalModelFromId(string $relation, $id)
{
    if (!$id) {
        return null;
    }

    // تحقق هل علاقة موجودة في الموديل الحالي
    if (!method_exists($this, $relation)) {
        return null;
    }

    // استدعاء علاقة الـ Eloquent مثل city()
    $relatedModel = $this->$relation()->getRelated();

    return $relatedModel->find($id);
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
