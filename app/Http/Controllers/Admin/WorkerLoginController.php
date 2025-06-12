<?php

namespace App\Http\Controllers\Admin;

use App\Models\WorkerLogin;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Auth\WorkerRegisterResource;

class WorkerLoginController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

        public function active(string $id)
    {
        $this->authorize('manage_system');
        $Worker = WorkerLogin::findOrFail($id);

        return $this->changeStatusSimple($Worker, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Worker = WorkerLogin::findOrFail($id);

        return $this->changeStatusSimple($Worker, 'notActive');
    }

      protected function getResourceClass(): string
    {
        return WorkerRegisterResource::class;
    }
}
