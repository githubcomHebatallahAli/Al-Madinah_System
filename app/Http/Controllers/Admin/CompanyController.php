<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Requests\Admin\CompanyRequest;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\CompanyResource;


class CompanyController extends Controller
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
        $Companies = Company::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Companies);

        return response()->json([
            'data' =>  CompanyResource::collection($Companies),
            'message' => "Show All Companies."
        ]);
    }


    public function create(CompanyRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'service_id', 'name', 'address','communication','description'
        ]), $this->prepareCreationMetaData());

        $Company = Company::create($data);

         return $this->respondWithResource($Company, "Company created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Company = Company::find($id);

        if (!$Company) {
            return response()->json([
                'message' => "Company not found."
            ], 404);
            }

    return $this->respondWithResource($Company, "Company retrieved for editing.");
        }

public function update(CompanyRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Company = Company::findOrFail($id);
    $oldData = $Company->toArray();

    $updateData = $request->only(['name','address','service_id','status','communication','description']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Company->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Company->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Company);
        return $this->respondWithResource($Company, "لا يوجد تغييرات فعلية");
    }

    $Company->update($updateData);
    $changedData = $Company->getChangedData($oldData, $Company->fresh()->toArray());
    $Company->changed_data = $changedData;
    $Company->save();

    $this->loadCommonRelations($Company);
    return $this->respondWithResource($Company, "تم تحديث شركه بنجاح");
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Company = Company::findOrFail($id);

        return $this->changeStatusSimple($Company, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Company = Company::findOrFail($id);

        return $this->changeStatusSimple($Company, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return CompanyResource::class;
    }
}
