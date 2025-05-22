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
use App\Http\Resources\Admin\TitleResource;

class TitleController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;

        public function showAll()
    {
        $this->authorize('manage_system');
        $Titles = Title::
        orderBy('created_at', 'desc')
        ->get();
         foreach ($Titles as $title) {
        $this->loadCreatorRelations($title);
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
        ]);
         $this->loadCreatorRelations($Title);
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
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();
           $Title =Title::findOrFail($id);
             $oldData = $Title->toArray();

           if (!$Title) {
            return response()->json([
                'message' => "Title not found."
            ], 404);
        }
           $Title->update([
           'branch_id'=> $request ->branch_id,
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
            ]);
            $this->loadCreatorRelations($Title);

        $changedData = $this->getChangedData($oldData, $Title->toArray());
        $Title->changed_data = $changedData;

           $Title->save();
           return response()->json([
            'data' =>new TitleResource($Title),
            'message' => " Update Title By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $this->authorize('manage_system');
      $addedById = $this->getAddedByIdOrFail();
       $addedByType = $this->getAddedByType();
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
    $Title->added_by = $addedById;
    $Title->added_by_type = $addedByType;
    $Title->save();

    $changedData = $this->getChangedData($oldData, $Title->toArray());
    $Title->changed_data = $changedData;
    $Title->save();
    $this->loadCreatorRelations($Title);


      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been active.'
      ]);
  }

     public function notActive(string $id)
  {
     $this->authorize('manage_system');
     $addedById = $this->getAddedByIdOrFail();
    $addedByType = $this->getAddedByType();
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
    $Title->added_by = $addedById;
    $Title->added_by_type = $addedByType;
    $Title->save();

    $changedData = $this->getChangedData($oldData, $Title->toArray());
    $Title->changed_data = $changedData;
    $Title->save();
    $this->loadCreatorRelations($Title);

      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been notActive.'
      ]);
  }
}
