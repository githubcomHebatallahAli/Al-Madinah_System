<?php

namespace App\Http\Controllers\Admin;

use App\Models\Title;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\TitleRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\TitleResource;

class TitleController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;

        public function showAll()
    {
        $this->authorize('manage_system');
        $Titles = Title::
        orderBy('created_at', 'desc')
        ->get();
         foreach ($Titles as $title) {
        $this->loadCreatorRelations($title);
        $this->loadUpdaterRelations($title);
    }

        return response()->json([
            'data' =>  TitleResource::collection($Titles),
            'message' => "Show All Titles."
        ]);
    }


    public function create(TitleRequest $request)
    {
       $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();

        $Title = Title::create([
            'branch_id'=> $request ->branch_id,
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
            'updated_by' => $addedById,
            'updated_by_type' => $addedByType
        ]);
         $this->loadCreatorRelations($Title);
         $this->loadUpdaterRelations($Title);

           return response()->json([
            'data' =>new TitleResource($Title),
            'message' => "Title Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_system');

        $Title = Title::with(['workers'])
        ->find($id);
            if (!$Title) {
                return response()->json([
                    'message' => "Title not found."
                ], 404);
            }
            $this->loadCreatorRelations($Title);
            $this->loadUpdaterRelations($Title);

            return response()->json([
                'data' => new TitleResource($Title),
                'message' => "Edit Title By ID Successfully."
            ]);
        }

        public function update(TitleRequest $request, string $id)
        {
          $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

           $Title =Title::find($id);
            //  $oldData = $Title->toArray();

           if (!$Title) {
            return response()->json([
                'message' => "Title not found."
            ], 404);
        }
         $oldData = $Title->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();
           $Title->update([
           'branch_id'=> $request ->branch_id,
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            'updated_by' => $updatedById,
            'updated_by_type' => $updatedByType
            ]);

        $changedData = $this->getChangedData($oldData, $Title->toArray());
        $Title->changed_data = $changedData;
           $Title->save();
            $this->loadCreatorRelations($Title);
            $this->loadUpdaterRelations($Title);
           return response()->json([
            'data' =>new TitleResource($Title),
            'message' => " Update Title By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $this->authorize('manage_system');
      $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();
      $Title =Title::findOrFail($id);

      if (!$Title) {
       return response()->json([
           'message' => "Title not found."
       ]);
   }
       $oldData = $Title->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Title->status = 'active';
    $Title->creationDate = $creationDate;
    $Title->creationDateHijri = $hijriDate;
    $Title->updated_by = $updatedById;
    $Title->updated_by_type = $updatedByType;
    $Title->save();

    $changedData = $this->getChangedData($oldData, $Title->toArray());
    $Title->changed_data = $changedData;
    $Title->save();
    $this->loadCreatorRelations($Title);
     $this->loadUpdaterRelations($Title);


      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been active.'
      ]);
  }

     public function notActive(string $id)
  {
     $this->authorize('manage_system');
     $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();
      $Title =Title::findOrFail($id);

      if (!$Title) {
       return response()->json([
           'message' => "Title not found."
       ]);
   }

          $oldData = $Title->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Title->status = 'notActive';
    $Title->creationDate = $creationDate;
    $Title->creationDateHijri = $hijriDate;
    $Title->updated_by = $updatedById;
    $Title->updated_by_type = $updatedByType;
    $Title->save();

    $changedData = $this->getChangedData($oldData, $Title->toArray());
    $Title->changed_data = $changedData;
    $Title->save();
    $this->loadCreatorRelations($Title);
    $this->loadUpdaterRelations($Title);

      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been notActive.'
      ]);
  }
}
