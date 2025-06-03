<?php

namespace App\Traits;

trait BranchManagerAccessTrait
{
    public function isBranchManager($user)
    {
        return $user->role_id === 2 && $user->worker && $user->worker->title && $user->worker->title->branch;
    }

    public function isSameBranch($user, $model)
    {
        if (! $user->worker || ! $user->worker->title) {
            return false;
        }
        return $user->worker->title->branch_id === $model->title->branch_id;
    }
}
