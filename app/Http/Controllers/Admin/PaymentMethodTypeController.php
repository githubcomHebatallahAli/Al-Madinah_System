<?php

namespace App\Http\Controllers\Admin;

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

class PaymentMethodTypeTypeController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

    public function showAll()
    {
        $this->authorize('manage_system');
        $PaymentMethodTypes = PaymentMethodType::orderBy('created_at', 'desc')->get();

         $this->loadRelationsForCollection($PaymentMethodTypes);

        return response()->json([
            'data' => PaymentMethodTypeResource::collection($PaymentMethodTypes),
            'message' => "All PaymentMethodTypes retrieved successfully."
        ]);
    }

     public function create(PaymentMethodTypeRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
             'type','payment_method_id'

        ]), $this->prepareCreationMetaData());

        $PaymentMethodType = PaymentMethodType::create($data);

         return $this->respondWithResource($PaymentMethodType, "PaymentMethodType created successfully.");
    }


public function update(PaymentMethodTypeRequest $request, string $id)
{
    $this->authorize('manage_system');
    $PaymentMethodType = PaymentMethodType::findOrFail($id);
    $oldData = $PaymentMethodType->toArray();

    $updateData = $request->only(['type','payment_method_id','status']);

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
