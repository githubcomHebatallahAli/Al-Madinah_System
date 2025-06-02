<?php

namespace App\Http\Controllers\Admin;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CampaignWorkerController extends Controller
{
    use HandleAddedByTrait, HijriDateTrait;
    public function addDelegatesToCampaign(Request $request, Campaign $campaign)
{
    $request->validate([
        'worker_ids' => 'required|array',
        'worker_ids.*' => 'exists:workers,id',
    ]);

    $data = [];
    $this->setAddedBy($data);
    $this->setUpdatedBy($data);

    $data = array_merge($data, [
        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        'creationDateHijri' => $this->getHijriDate(),
    ]);

    DB::transaction(function () use ($request, $campaign, $data) {
        $workersToAdd = [];

        foreach ($request->worker_ids as $workerId) {
            if (!$campaign->workers()->where('worker_id', $workerId)->exists()) {
                $workersToAdd[$workerId] = $data;
            }
        }

        if (!empty($workersToAdd)) {
            $campaign->workers()->attach($workersToAdd);
            $campaign->increment('workersCount', count($workersToAdd));
        }
    });

    return response()->json([
        'message' => 'تمت إضافة المندوبين إلى الحملة بنجاح',
        'data' => $campaign->load(['workers.workerLogin.role', 'workers.title'])
    ], 200);
}


public function removeDelegatesFromCampaign(Request $request, Campaign $campaign)
{
    $request->validate([
        'worker_ids' => 'required|array',
        'worker_ids.*' => 'exists:workers,id',
    ]);

    $updateData = [];
    $this->setUpdatedBy($updateData);

    DB::transaction(function () use ($request, $campaign, $updateData) {

        $campaign->workers()
            ->whereIn('worker_id', $request->worker_ids)
            ->update($updateData);

        $countToRemove = $campaign->workers()
            ->whereIn('worker_id', $request->worker_ids)
            ->count();

        if ($countToRemove > 0) {
            $campaign->workers()->detach($request->worker_ids);
            $campaign->decrement('workersCount', $countToRemove);
        }
    });

    return response()->json([
        'message' => 'تم فصل المندوبين عن الحملة بنجاح',
        'data' => $campaign->load(['workers.workerLogin.role', 'workers.title'])
    ], 200);
}

/**
 * الحصول على قائمة مندوبي الحملة
 */
public function getCampaignDelegates(Campaign $campaign)
{
    $workers = $campaign->workers()
        ->with([
            'workerLogin.role',
            'title',
        ])
        ->get();

    return response()->json([
        'data' => $workers,
        'message' => 'تم جلب بيانات مندوبي الحملة بنجاح'
    ], 200);
}
}
