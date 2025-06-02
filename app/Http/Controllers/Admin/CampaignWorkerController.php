<?php

namespace App\Http\Controllers\Admin;

use App\Models\Worker;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CampaignWorkerResource;

class CampaignWorkerController extends Controller
{
    use HandleAddedByTrait, HijriDateTrait;
public function addDelegatesToCampaign(Request $request, $campaignId)
{
    $campaign = Campaign::withCount('workers')->findOrFail($campaignId);

    $request->validate([
        'worker_ids' => 'required|array|min:1',
        'worker_ids.*' => [
            'exists:workers,id',
            function ($attribute, $value, $fail) use ($campaign) {
                if ($campaign->workers()->where('worker_id', $value)->exists()) {
                    $fail('المندوب مضاف بالفعل للحملة');
                }
            }
        ]
    ]);

    $data = [];
    $this->setAddedBy($data);
    $this->setUpdatedBy($data);

    $data = array_merge($data, [
        'creation_date' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        'creation_date_hijri' => $this->getHijriDate(),
    ]);

    DB::transaction(function () use ($request, $campaign, $data) {
        $eligibleWorkers = Worker::whereIn('id', $request->worker_ids)
            ->where('status', 'active')
            ->where('dashboardAccess', 'ok')
            ->whereHas('workerLogin', fn($q) => $q->where('role_id', 3))
            ->get();

        // إضافة المندوبين المؤهلين
        foreach ($eligibleWorkers as $worker) {
            $campaign->workers()->attach($worker->id, $data);
        }

        // تحديث العداد
        $campaign->workers_count = $campaign->workers()->count();
        $campaign->save();
    });

    // جلب المندوبين المضافين مع علاقاتهم
    $addedWorkers = $campaign->workers()
        ->whereIn('worker_id', $request->worker_ids)
        ->with([
            'workerLogin.role',
            'title' // علاقة title من نموذج Worker
        ])
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'تمت إضافة المندوبين إلى الحملة بنجاح',
        'data' => [
            'campaign_id' => $campaign->id,
            'added_workers' => CampaignWorkerResource::collection($addedWorkers),
            'added_count' => $addedWorkers->count(),
            'total_workers_count' => $campaign->workers_count
        ]
    ], 200);
}


public function removeDelegatesFromCampaign(Request $request, $campaignId)
{
    $campaign = Campaign::withCount('workers')->findOrFail($campaignId);

    $request->validate([
        'worker_ids' => 'required|array|min:1',
        'worker_ids.*' => [
            'exists:workers,id',
            function ($attribute, $value, $fail) use ($campaign) {
                if (!$campaign->workers()->where('worker_id', $value)->exists()) {
                    $fail("المندوب ID {$value} غير موجود في الحملة");
                }
            }
        ]
    ]);

    $updateData = [
        'updated_at' => now()->timezone('Asia/Riyadh')
    ];
    $this->setUpdatedBy($updateData);

    $removedCount = 0;

    DB::transaction(function () use ($request, $campaign, $updateData, &$removedCount) {
        // تحديث بيانات updated_by قبل الفصل
        $campaign->workers()
            ->whereIn('worker_id', $request->worker_ids)
            ->update($updateData);

        $removedCount = $campaign->workers()
            ->whereIn('worker_id', $request->worker_ids)
            ->count();

        if ($removedCount > 0) {
            $campaign->workers()->detach($request->worker_ids);
            $campaign->decrement('workersCount', $removedCount);
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'تم فصل المندوبين بنجاح',
        'data' => [
            'campaign_id' => $campaign->id,
            'removed_workers_count' => $removedCount,
            'remaining_workers_count' => $campaign->workersCount - $removedCount,
            'removed_workers_ids' => $request->worker_ids
        ]
    ], 200);
}


public function getCampaignDelegates($campaignId)
{
    $campaign = Campaign::withCount('workers')->findOrFail($campaignId);

    $workers = $campaign->workers()
        ->with([
            'workerLogin.role',
        ])
        ->get();

    return response()->json([
        'campaign_id' => (int)$campaignId,
        'campaign_name' => $campaign->name,
        'added_workers' => CampaignWorkerResource::collection($workers),

        'count' => $campaign->workersCount,
        'message' => 'تم جلب بيانات مندوبي الحملة بنجاح'
    ], 200);
}
}
