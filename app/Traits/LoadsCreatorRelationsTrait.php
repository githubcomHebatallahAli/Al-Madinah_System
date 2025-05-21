<?php

namespace App\Traits;

trait LoadsCreatorRelationsTrait
{
    public function loadCreatorRelations($model): void
    {
        $relations = ['creator'];

        if ($model->added_by_type === \App\Models\Admin::class) {
            $relations[] = 'creator.role';
            $relations[] = 'creator.branch'; // لو موجودة
        }

        if ($model->added_by_type === \App\Models\Worker::class) {
            $relations[] = 'creator.workerLogin.role';
            $relations[] = 'creator.branch'; // لو موجودة
        }

        $model->loadMissing($relations);
    }
}
