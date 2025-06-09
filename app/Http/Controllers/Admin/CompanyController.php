<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Requests\Admin\CompanyRequest;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\CompanyResource;
use App\Http\Resources\Admin\ShowAllCompanyResource;


class CompanyController extends Controller
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

    $searchTerm = $request->input('search', '');

    $query = Company::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->service_id) {
        $query->where('service_id', $request->service_id);
    }

      if ($request->has('type') && in_array($request->type, ['direct', 'supply'])) {
        $query->where('type', $request->type);
    }

    $Companies = $query->paginate(10);

    return response()->json([
        'data' => ShowAllCompanyResource::collection($Companies),
        'pagination' => [
            'total' => $Companies->total(),
            'count' => $Companies->count(),
            'per_page' => $Companies->perPage(),
            'current_page' => $Companies->currentPage(),
            'total_pages' => $Companies->lastPage(),
            'next_page_url' => $Companies->nextPageUrl(),
            'prev_page_url' => $Companies->previousPageUrl(),
        ],
        'message' => "Show All Companies."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Company::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->service_id) {
        $query->where('service_id', $request->service_id);
    }

if ($request->filled('type') && in_array($request->type, ['direct', 'supply'])) {
    $query->where('type', $request->type);
}


    $Companies = $query->get();

    return response()->json([
        'data' => ShowAllCompanyResource::collection($Companies),
        'message' => "Show All Companies."
    ]);
}


    public function create(CompanyRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'service_id', 'name', 'address','communication','description','type'
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

    $updateData = $request->only(['name','address','service_id','status','communication','description','type']);

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
