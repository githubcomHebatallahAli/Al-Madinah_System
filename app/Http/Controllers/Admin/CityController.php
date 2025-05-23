<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Http\Resources\Admin\CityResource;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;


class CityController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;

        public function showAll()
    {
        $this->authorize('manage_users');
       $Cities = City::orderBy('created_at', 'desc')
        ->get();
          foreach ($Cities as $City) {
        $this->loadCreatorRelations($City);
        $this->loadUpdaterRelations($City);
    }

        return response()->json([
            'data' =>  CityResource::collection($City),
            'message' => "Show All Cities."
        ]);
    }


    public function create(CityRequest $request)
    {
        $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();

        $City = City::create([
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
            'updated_by' => $addedById,
            'updated_by_type' => $addedByType
        ]);
        $this->loadCreatorRelations($City);
         $this->loadUpdaterRelations($City);
           return response()->json([
            'data' =>new CityResource($City),
            'message' => "City Created Successfully."
        ]);
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
        $this->loadCreatorRelations($City);
         $this->loadUpdaterRelations($City);

            return response()->json([
                'data' => new CityResource($City),
                'message' => "Edit City By ID Successfully."
            ]);
        }

        public function update(CityRequest $request, string $id)
        {
          $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
           $City =City::find($id);

           if (!$City) {
            return response()->json([
                'message' => "City not found."
            ], 404);
        }

        $oldData = $City->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();
           $City->update([
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            'updated_by' => $updatedById,
            'updated_by_type' => $updatedByType
            ]);


        $changedData = $this->getChangedData($oldData, $City->toArray());
        $City->changed_data = $changedData;

        $City->save();
        $this->loadCreatorRelations($City);
         $this->loadUpdaterRelations($City);
           return response()->json([
            'data' =>new CityResource($City),
            'message' => " Update City By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $this->authorize('manage_users');
      $City =City::find($id);

      if (!$City) {
       return response()->json([
           'message' => "City not found."
       ]);
   }
        $oldData = $City->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $City->status = 'active';
    $City->creationDate = $creationDate;
    $City->creationDateHijri = $hijriDate;
    $City->updated_by = $updatedById;
    $City->updated_by_type = $updatedByType;
    $City->save();

    $changedData = $this->getChangedData($oldData, $City->toArray());
    $City->changed_data = $changedData;
    $City->save();
    $this->loadCreatorRelations($City);
    $this->loadUpdaterRelations($City);

      return response()->json([
          'data' => new CityResource($City),
          'message' => 'City has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');
      $City =City::find($id);

      if (!$City) {
       return response()->json([
           'message' => "City not found."
       ]);
   }

        $oldData = $City->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();


    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $City->status = 'notActive';
    $City->creationDate = $creationDate;
    $City->creationDateHijri = $hijriDate;
    $City->updated_by = $updatedById;
    $City->updated_by_type = $updatedByType;
    $City->save();

    $changedData = $this->getChangedData($oldData, $City->toArray());
    $City->changed_data = $changedData;
    $City->save();
    $this->loadCreatorRelations($City);
    $this->loadUpdaterRelations($City);


      return response()->json([
          'data' => new CityResource($City),
          'message' => 'City has been notActive.'
      ]);
  }
}
