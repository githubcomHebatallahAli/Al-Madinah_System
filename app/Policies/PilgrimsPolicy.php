<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Pilgrim;
use App\Models\WorkerLogin;
use Illuminate\Auth\Access\HandlesAuthorization;

class PilgrimsPolicy
{
    use HandlesAuthorization;

    public function create($user)
    {
        if ($user instanceof WorkerLogin) {
            $worker = $user->worker;

            return $user->role_id === 3 &&
                   $worker &&
                   $worker->status === 'active' &&
                   $worker->dashboardAccess === 'ok';
        }

        return false;
    }

    public function update($user, Pilgrim $pilgrims)
    {
        if ($user instanceof WorkerLogin) {
            $worker = $user->worker;

            return $user->role_id === 3 &&
                   $worker &&
                   $worker->status === 'active' &&
                   $worker->dashboardAccess === 'ok';
        }

        return false;
    }

    public function active($user, Pilgrim $pilgrims)
    {
        return $this->update($user, $pilgrims);
    }

    public function notActive($user, Pilgrim $pilgrims)
    {
        return $this->update($user, $pilgrims);
    }

    public function showAll($user)
    {
        if ($user instanceof Admin) {
            return $user->role_id === 1 && $user->status === 'active';
        }

        if ($user instanceof WorkerLogin) {
            $worker = $user->worker;

            return in_array($user->role_id, [2, 3]) &&
                   $worker &&
                   $worker->status === 'active' &&
                   $worker->dashboardAccess === 'ok';
        }

        return false;
    }


    public function edit($user,  $pilgrims)
    {
        return $this->showAll($user);
    }
}
