<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait TracksChangesTrait
{

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

        // تحقق هل هناك تغيير أو من الحقول التي يجب تتبعها دائماً
        if (in_array($key, $alwaysTrack) || $oldValue != $newValue) {
            $changed[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];

            // إذا كان الحقل هو علاقة (مثلاً ينتهي بـ _id)
            if (str_ends_with($key, '_id')) {
                $relationName = str_replace('_id', '', $key);

                // جلب النموذج القديم المرتبط
                $oldModel = $this->getOriginalModelFromId($relationName, $oldValue);
                // جلب النموذج الجديد المرتبط
                $newModel = $this->getOriginalModelFromId($relationName, $newValue);

                $changed[$key]['old_name'] = $oldModel?->name ?? null;
                $changed[$key]['new_name'] = $newModel?->name ?? null;
            }
        }
    }

    return $changed;
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
