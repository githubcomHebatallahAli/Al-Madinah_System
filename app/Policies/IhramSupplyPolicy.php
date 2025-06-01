<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Worker;
use App\Models\IhramSupply;
use App\Models\WorkerLogin;
use Illuminate\Auth\Access\HandlesAuthorization;

class IhramSupplyPolicy
{
    use HandlesAuthorization;

    public function create($user)
    {
        if ($user instanceof WorkerLogin) {
            $worker = $user->worker;

            return $user->role_id === 6 &&
                   $worker &&
                   $worker->status === 'active' &&
                   $worker->dashboardAccess === 'ok';
        }

        return false;
    }

    public function update($user, IhramSupply $ihramSupply)
    {
        if ($user instanceof WorkerLogin) {
            $worker = $user->worker;

            return $user->role_id === 6 &&
                   $worker &&
                   $worker->status === 'active' &&
                   $worker->dashboardAccess === 'ok';
        }

        return false;
    }

    public function active($user, IhramSupply $ihramSupply)
    {
        return $this->update($user, $ihramSupply);
    }

    public function notActive($user, IhramSupply $ihramSupply)
    {
        return $this->update($user, $ihramSupply);
    }

    public function showAll($user)
    {
        if ($user instanceof Admin) {
            return $user->role_id === 1 && $user->status === 'active';
        }

        if ($user instanceof WorkerLogin) {
            $worker = $user->worker;

            return in_array($user->role_id, [2, 6]) &&
                   $worker &&
                   $worker->status === 'active' &&
                   $worker->dashboardAccess === 'ok';
        }

        return false;
    }


    public function edit($user, IhramSupply $ihramSupply)
    {
        return $this->showAll($user);
    }


}
