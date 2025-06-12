<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Models\PaymentMethodType;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\PaymentMethodTypeRequest;
use App\Http\Resources\Admin\PaymentMethodTypeResource;
use App\Http\Resources\Admin\ShowAllPaymentMethodTypeResource;

class PaymentMethodTypeController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

public function showAllWithPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = PaymentMethodType::query();

    // if ($request->has('payment_method_id')) {
    //     $query->where('payment_method_id', $request->payment_method_id);
    // }

        if ($request->filled('payment_method_id')) {
        $query->where('payment_method_id', $request->payment_method_id);
    }

        if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }


    $PaymentMethodType = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllPaymentMethodTypeResource::collection($PaymentMethodType),
        'pagination' => [
            'total' => $PaymentMethodType->total(),
            'count' => $PaymentMethodType->count(),
            'per_page' => $PaymentMethodType->perPage(),
            'current_page' => $PaymentMethodType->currentPage(),
            'total_pages' => $PaymentMethodType->lastPage(),
            'next_page_url' => $PaymentMethodType->nextPageUrl(),
            'prev_page_url' => $PaymentMethodType->previousPageUrl(),
        ],
        'message' => "Show All Payment Method Type."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = PaymentMethodType::query();

            if ($request->filled('payment_method_id')) {
        $query->where('payment_method_id', $request->payment_method_id);
    }

        if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }


    $PaymentMethodType = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllPaymentMethodTypeResource::collection($PaymentMethodType),
        'message' => "Show All Payment Method Type."
    ]);
}


     public function create(PaymentMethodTypeRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
             'type','payment_method_id','by'

        ]), $this->prepareCreationMetaData());

        $PaymentMethodType = PaymentMethodType::create($data);

         return $this->respondWithResource($PaymentMethodType, "PaymentMethodType created successfully.");
    }


public function update(PaymentMethodTypeRequest $request, string $id)
{
    $this->authorize('manage_system');
    $PaymentMethodType = PaymentMethodType::findOrFail($id);
    $oldData = $PaymentMethodType->toArray();

    $updateData = $request->only(['type','payment_method_id','status','by']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $PaymentMethodType->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($PaymentMethodType->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($PaymentMethodType);
        return $this->respondWithResource($PaymentMethodType, "لا يوجد تغييرات فعلية");
    }

    $PaymentMethodType->update($updateData);

    $changedData = $PaymentMethodType->getChangedData($oldData, $PaymentMethodType->fresh()->toArray());
    $PaymentMethodType->changed_data = $changedData;
    $PaymentMethodType->save();



    $this->loadCommonRelations($PaymentMethodType);
    return $this->respondWithResource($PaymentMethodType, "تم تحديث  نوع وسيله الدفع بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $PaymentMethodType = PaymentMethodType::find($id);

    if (!$PaymentMethodType) {
        return response()->json(['message' => "PaymentMethodType not found."], 404);
    }

    return $this->respondWithResource($PaymentMethodType, "PaymentMethodType retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $PaymentMethodType = PaymentMethodType::findOrFail($id);

        return $this->changeStatusSimple($PaymentMethodType, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $PaymentMethodType = PaymentMethodType::findOrFail($id);

        return $this->changeStatusSimple($PaymentMethodType, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return PaymentMethodTypeResource::class;
    }
}
