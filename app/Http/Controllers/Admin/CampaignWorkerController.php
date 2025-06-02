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
        $eligibleWorkers = Worker::whereIn('id', $request->worker_ids)
            ->where('status', 'active')
            ->where('dashboardAccess', 'ok')
            ->whereHas('workerLogin', fn($q) => $q->where('role_id', 3))
            ->get();

        // الطريقة الموصى بها في Laravel 12
        $campaign->workers()->attach(
            $eligibleWorkers->pluck('id')->toArray(),
            $data
        );


        $campaign->refresh()->workersCount = $campaign->workers()->count();
        $campaign->save();
    });

    $addedWorkers = $campaign->workers()
        ->whereIn('worker_id', $request->worker_ids)
        ->with(['workerLogin.role','workers.title'])
        ->get();

    return response()->json([
        'message' => 'تمت إضافة المندوبين إلى الحملة بنجاح',
        'data' => [
            'campaign_id' => $campaign->id,
            'added_workers' => CampaignWorkerResource::collection($addedWorkers),
            'added_count' => $addedWorkers->count(),
            'remaining_workers_count' => $campaign->workersCount
        ]
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

    $removedWorkers = collect(); // سيتم ملؤه داخل المعاملة

    DB::transaction(function () use ($request, $campaign, $updateData, &$removedWorkers) {
        // جلب بيانات المندوبين المرتبطين قبل الحذف
        $removedWorkers = $campaign->workers()
            ->whereIn('worker_id', $request->worker_ids)
            ->with(['workerLogin.role']) // لو كنت تحتاج معلومات الدخول
            ->get();

        // تحديث جدول pivot فقط بدون تحديث جدول workers لتفادي التعارض في الأعمدة
        DB::table('campaign_workers')
            ->where('campaign_id', $campaign->id)
            ->whereIn('worker_id', $request->worker_ids)
            ->update(array_merge($updateData, [
                'updated_at' => now()
            ]));

        // حذف المندوبين من الحملة
        $countToRemove = $removedWorkers->count();
        if ($countToRemove > 0) {
            $campaign->workers()->detach($request->worker_ids);
            $campaign->decrement('workersCount', $countToRemove);
        }
    });

    return response()->json([
        'message' => 'تم فصل المندوبين عن الحملة بنجاح',
        'removed_workers' => CampaignWorkerResource::collection($removedWorkers),
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
