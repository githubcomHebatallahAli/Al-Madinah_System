<?php

namespace App\Traits;

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
}
