<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Worker;

trait LoadsUpdaterRelationsTrait
{
    public function loadUpdaterRelations($model): void
    {
        $relations = ['updater'];

        if ($model->updated_by_type === Admin::class) {
            $relations[] = 'updater.role';
        }

        if ($model->updated_by_type === Worker::class) {
            $relations[] = 'updater.workerLogin.role';
            $relations[] = 'updater.branch';
        }

        $model->loadMissing($relations);
    }
}
