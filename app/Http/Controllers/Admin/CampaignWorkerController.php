<?php

namespace App\Http\Controllers\Admin;

use App\Models\Worker;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CampaignWorkerController extends Controller
{
    use HandleAddedByTrait, HijriDateTrait;
public function addDelegatesToCampaign(Request $request, $campaignId)
{
    $campaign = Campaign::findOrFail($campaignId);

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
        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        'creationDateHijri' => $this->getHijriDate(),
    ]);

    DB::transaction(function () use ($request, $campaign, $data) {
        $workersToAdd = [];

        // جلب المندوبين الذين يستوفون الشروط
        $delegateWorkers = Worker::whereIn('id', $request->worker_ids)
            ->where('status', 'active')
            ->where('dashboardAccess', 'ok')
            ->pluck('id')
            ->toArray();

        foreach ($delegateWorkers as $workerId) {
            if (!$campaign->workers()->where('worker_id', $workerId)->exists()) {
                $workersToAdd[$workerId] = $data;
            }
        }

        if (!empty($workersToAdd)) {
            $campaign->workers()->attach($workersToAdd);
            $campaign->increment('workersCount', count($workersToAdd));
        }
    });

    // جلب بيانات المندوبين المضافين حديثاً
    $addedWorkers = $campaign->workers()
        ->whereIn('worker_id', $request->worker_ids)
        ->with(['workerLogin.role', 'title'])
        ->get();

    return response()->json([
        'message' => 'تمت إضافة المندوبين إلى الحملة بنجاح',
        'data' => $addedWorkers,

    ], 200);
}


public function removeDelegatesFromCampaign(Request $request, $campaignId)
{
    $campaign = Campaign::findOrFail($campaignId);

    $request->validate([
        'worker_ids' => 'required|array|min:1',
        'worker_ids.*' => [
            'exists:workers,id',
            function ($attribute, $value, $fail) use ($campaign) {
                if (!$campaign->workers()->where('worker_id', $value)->exists()) {
                    $fail('المندوب غير موجود في الحملة');
                }
            }
        ]
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


public function getCampaignDelegates($campaignId)
{
    $campaign = Campaign::withCount('workers')->findOrFail($campaignId);

    $workers = $campaign->workers()
        ->with([
            'workerLogin.role',
            'title',
        ])
        ->get();

    return response()->json([
        'campaign_id' => (int)$campaignId,
        'campaign_name' => $campaign->name,
        'workers' => $workers,
        'count' => $campaign->workers_count,
        'message' => 'تم جلب بيانات مندوبي الحملة بنجاح'
    ], 200);
}
}
