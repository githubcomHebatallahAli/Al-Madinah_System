<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\SupplierRequest;
use App\Http\Resources\Admin\SupplierResource;

class SupplierController extends Controller
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
        $Supplies = Supplier::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Supplies);

        return response()->json([
            'data' =>  SupplierResource::collection($Supplies),
            'message' => "Show All Supplies."
        ]);
    }


    public function create(SupplierRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'company_id', 'name','communication'
        ]), $this->prepareCreationMetaData());

        $Supplier = Supplier::create($data);

         return $this->respondWithResource($Supplier, "Supplier created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Supplier = Supplier::find($id);

        if (!$Supplier) {
            return response()->json([
                'message' => "Supplier not found."
            ], 404);
            }

    return $this->respondWithResource($Supplier, "Supplier retrieved for editing.");
        }

public function update(SupplierRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Supplier = Supplier::findOrFail($id);
    $oldData = $Supplier->toArray();

    $updateData = $request->only(['name','company_id','status','communication']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Supplier->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Supplier->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Supplier);
        return $this->respondWithResource($Supplier, "لا يوجد تغييرات فعلية");
    }

    $Supplier->update($updateData);
    $changedData = $Supplier->getChangedData($oldData, $Supplier->fresh()->toArray());
    $Supplier->changed_data = $changedData;
    $Supplier->save();

    $this->loadCommonRelations($Supplier);
    return $this->respondWithResource($Supplier, "تم تحديث المورد بنجاح");
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Supplier = Supplier::findOrFail($id);

        return $this->changeStatusSimple($Supplier, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Supplier = Supplier::findOrFail($id);

        return $this->changeStatusSimple($Supplier, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return SupplierResource::class;
    }
}
