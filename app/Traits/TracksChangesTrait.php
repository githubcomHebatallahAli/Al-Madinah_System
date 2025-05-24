<?php

namespace App\Traits;

trait TracksChangesTrait
{
    /**
     * Compare two arrays and return the changed data
     */
    public function getChangedData(array $oldData, array $newData): array
    {
        $changedData = [];
        $ignoredFields = $this->getIgnoredFieldsForTracking();

        foreach ($newData as $key => $value) {
            if (array_key_exists($key, $oldData) &&
                !in_array($key, $ignoredFields) &&
                $oldData[$key] != $value) {
                $changedData[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }

        return $changedData;
    }


    public function hasRealChanges(): bool
    {
        // إذا كان الموديل لا يحتوي على الحقول المطلوبة
        if (!isset($this->added_by) || !isset($this->updated_by)) {
            return false;
        }

        // إذا كان هناك changed_data غير فارغ فهناك تغييرات
        if (!empty($this->changed_data)) {
            return true;
        }

        // إذا كان updated_by مختلف عن added_by فهناك تغيير
        return $this->updated_by != $this->added_by ||
               $this->updated_by_type != $this->added_by_type;
    }
    protected function getIgnoredFieldsForTracking(): array
    {
        return [
            'updated_at',
            'updated_by',
            'updated_by_type',
            'changed_data',
            'creationDate',
            'creationDateHijri',
            'created_at',
            'added_by',
            'added_by_type'
        ];
    }
}
