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
        }

        if ($model->added_by_type === Worker::class) {
            $relations[] = 'creator.workerLogin.role';
            $relations[] = 'creator.branch'; 
        }

        $model->loadMissing($relations);
    }
}

