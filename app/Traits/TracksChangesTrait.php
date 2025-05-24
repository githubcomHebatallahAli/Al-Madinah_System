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

    /**
     * Check if there are any real changes
     */
    public function hasRealChanges($model): bool
    {
        // إذا كان هناك changed_data غير فارغ فهناك تغييرات
        if (!empty($model->changed_data)) {
            return true;
        }

        // إذا كان updated_by مختلف عن added_by فهناك تغيير
        if ($model->updated_by != $model->added_by ||
            $model->updated_by_type != $model->added_by_type) {
            return true;
        }

        return false;
    }

    /**
     * Fields to ignore when tracking changes
     */
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
