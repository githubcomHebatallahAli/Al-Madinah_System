<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Worker;

trait LoadsCreatorRelationsTrait
{
    public function loadCreatorRelations($model): void
    {
        $relations = ['creator'];

        if ($model->added_by_type === Admin::class) {
            $relations[] = 'creator.role';
            // ما نحمّلوش branch، لأنه مش مرتبط
        }

        if ($model->added_by_type === Worker::class) {
            $relations[] = 'creator.workerLogin.role';
            $relations[] = 'creator.branch'; // العامل مرتبط بفرع
        }

        $model->loadMissing($relations);
    }
}

