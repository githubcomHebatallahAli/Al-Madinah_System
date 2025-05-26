<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Http\Resources\Admin\CityResource;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;


class CityController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

        public function showAll()
    {
        $this->authorize('manage_users');
       $Cities = City::orderBy('created_at', 'desc')
        ->get();
        $this->loadRelationsForCollection($Cities);

        return response()->json([
            'data' =>  CityResource::collection($Cities),
            'message' => "Show All Cities."
        ]);
    }


    public function create(CityRequest $request)
    {
        $this->authorize('manage_users');
           $data = array_merge($request->only([
             'name'
        ]), $this->prepareCreationMetaData());

        $City = City::create($data);

         return $this->respondWithResource($City, "City created successfully.");
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');

       $City = City::with(['branches'])->find($id);

            if (!$City) {
                return response()->json([
                    'message' => "City not found."
                ], 404);
            }
    return $this->respondWithResource($City, "City retrieved for editing.");
        }


public function update(CityRequest $request, string $id)
{
    $this->authorize('manage_users');
    $city = City::findOrFail($id);
    $oldData = $city->toArray();

    $updateData = $request->only(['name', 'status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $city->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($city->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($city);
        return $this->respondWithResource($city, "لا يوجد تغييرات فعلية");
    }

      $city->update($updateData);

    $changedData = $city->getChangedData($oldData, $city->fresh()->toArray());

    $city->changed_data = $changedData;
    $city->save();

    $this->loadCommonRelations($city);
    return $this->respondWithResource($city, "تم تحديث المدينة بنجاح");
}

    //         public function update(CityRequest $request, string $id)
    //     {
    //       $this->authorize('manage_system');
    //     $hijriDate = $this->getHijriDate();
    //     $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    //        $City =City::find($id);
    //         //  $oldData = $City->toArray();

    //        if (!$City) {
    //         return response()->json([
    //             'message' => "City not found."
    //         ], 404);
    //     }
    //      $oldData = $City->toArray();
    //     $updatedById = $this->getUpdatedByIdOrFail();
    //     $updatedByType = $this->getUpdatedByType();
    //        $City->update([
    //     //    'branch_id'=> $request ->branch_id,
    //         "name" => $request->name,
    //         'creationDate' => $gregorianDate,
    //         'creationDateHijri' => $hijriDate,
    //         'status'=> $request-> status ?? 'active',
    //         'updated_by' => $updatedById,
    //         'updated_by_type' => $updatedByType
    //         ]);

    //     $changedData = $this->getChangedData($oldData, $City->toArray());
    //     $City->changed_data = $changedData;
    //        $City->save();
    //         $this->loadCreatorRelations($City);
    //         $this->loadUpdaterRelations($City);
    //        return response()->json([
    //         'data' =>new CityResource($City),
    //         'message' => " Update City By Id Successfully."
    //     ]);
    // }

    public function active(string $id)
    {
         $this->authorize('manage_users');
        $City = City::findOrFail($id);

        return $this->changeStatusSimple($City, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_users');
        $City = City::findOrFail($id);

        return $this->changeStatusSimple($City, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return CityResource::class;
    }
}
