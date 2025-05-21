<?php

namespace App\Traits;

trait LoadsCreatorRelationsTrait
{
    /**
     * Load default creator relationships (for added_by info).
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection $model
     * @return void
     */
    public function loadCreatorRelations($model): void
    {
        $relations = [
            'creator',
            'creator.workerLogin.role',
            'creator.role',
            'creator.branch',
        ];

        $model->loadMissing($relations);
    }
}
