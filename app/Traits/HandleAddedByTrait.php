<?php



namespace App\Traits;

use App\Models\Admin;
use App\Models\WorkerLogin;
use Illuminate\Support\Facades\Auth;

trait HandleAddedByTrait
{
    public function getAddedByIdOrFail()
    {
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->role_id == 1) {
            return Auth::guard('admin')->id();
        }

        if (Auth::guard('worker')->check() && Auth::guard('worker')->user()->role_id == 2) {
            return Auth::guard('worker')->id();
        }

        abort(response()->json([
            'message' => 'Unauthorized: Only SuperAdmin or BranchManager can perform this action.'
        ], 403));
    }

    public function getAddedByType(): string
    {
        if (Auth::guard('admin')->check()) {
            return Admin::class;
        }

        if (Auth::guard('worker')->check()) {
            return WorkerLogin::class;
        }

        abort(response()->json([
            'message' => 'Unauthorized: Unknown user type.'
        ], 403));
    }

    public function getUpdatedByIdOrFail()
    {
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->role_id == 1) {
            return Auth::guard('admin')->id();
        }

        if (Auth::guard('worker')->check() && Auth::guard('worker')->user()->role_id == 2) {
            return Auth::guard('worker')->id();
        }

        abort(response()->json([
            'message' => 'Unauthorized: Only SuperAdmin or BranchManager can perform this action.'
        ], 403));
    }

    public function getUpdatedByType(): string
    {
        if (Auth::guard('admin')->check()) {
            return Admin::class;
        }

        if (Auth::guard('worker')->check()) {
            return WorkerLogin::class;
        }

        abort(response()->json([
            'message' => 'Unauthorized: Unknown user type.'
        ], 403));
    }

     public function setAddedBy(array &$data): void
    {
        $data['added_by_id'] = $this->getAddedByIdOrFail();
        $data['added_by_type'] = $this->getAddedByType();
    }

    public function setUpdatedBy(array &$data): void
    {
        $data['updated_by_id'] = $this->getUpdatedByIdOrFail();
        $data['updated_by_type'] = $this->getUpdatedByType();
    }
}











