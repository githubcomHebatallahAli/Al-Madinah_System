<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CloseRequest;
use App\Models\Close;

class CloseController extends Controller
{
        public function getStatus()
    {
        $this->authorize('manage_system');
        $status = Close::first();

        return response()->json([
            'status' => $status && $status->is_active ? true : false

        ]);
    }


    public function toggleStatus(CloseRequest $request)
    {
        $this->authorize('manage_system');
        $status = Close::firstOrCreate([], []);
        $status->is_active = $request->status;
        $status->save();

        return response()->json([
            'message' => 'Status updated successfully.',
            'status' => $status->is_active
        ]);
    }
}
