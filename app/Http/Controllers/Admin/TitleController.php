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
use App\Http\Resources\Admin\TitleResource;

class TitleController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;

        public function showAll()
    {
           dd([
        'Auth::user()' => Auth::user(),
        'Auth::guard("admin")->user()' => Auth::guard('admin')->user(),
        'Auth::guard("worker")->user()' => Auth::guard('worker')->user(),
        'user_role_id' => Auth::user()->role_id ?? null
    ]);
        $this->authorize('manage_system');
        $Title = Title::with('creator')
        ->orderBy('created_at', 'desc')
        ->get();

                  return response()->json([
                      'data' =>  TitleResource::collection($Title),
                      'message' => "Show All Titlees."
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
        $Title->load('creator');
           return response()->json([
            'data' =>new TitleResource($Title),
            'message' => "Title Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_system');

        $Title = Title::with(['workers','creator'])
        ->find($id);
            if (!$Title) {
                return response()->json([
                    'message' => "Title not found."
                ], 404);
            }

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
            $Title->load('creator');

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
    $Title->load('creator');

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
    $Title->load('creator');

      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been notActive.'
      ]);
  }
}
