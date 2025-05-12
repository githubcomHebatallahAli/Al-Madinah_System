<?php

namespace App\Http\Controllers\Admin;

use App\Models\Title;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TitleRequest;
use App\Http\Resources\Admin\TitleResource;

class TitleController extends Controller
{
        use HijriDateTrait;
    use TracksChangesTrait;

        public function showAll()
    {
        $this->authorize('manage_users');
        $Title = Title::orderBy('created_at', 'desc')
        ->get();

                  return response()->json([
                      'data' =>  TitleResource::collection($Title),
                      'message' => "Show All Titlees."
                  ]);
    }


    public function create(TitleRequest $request)
    {
        $this->authorize('manage_users');
           $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

        $Title = Title::create([
            'branch_id'=> $request ->branch_id,
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'admin_id' => auth()->id(),
            'status' => 'active',
        ]);
           return response()->json([
            'data' =>new TitleResource($Title),
            'message' => "Title Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');

        $Title = Title::with('workers')
        ->find($id);


            if (!$Title) {
                return response()->json([
                    'message' => "Title not found."
                ], 404);
            }

            // $this->authorize('edit',$Title);

            return response()->json([
                'data' => new TitleResource($Title),
                'message' => "Edit Title By ID Successfully."
            ]);
        }

        public function update(TitleRequest $request, string $id)
        {
          $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
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
            'admin_id' => auth()->id(),

            ]);

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
      $this->authorize('manage_users');
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
    $Title->admin_id = auth()->id();
    $Title->save();

    $changedData = $this->getChangedData($oldData, $Title->toArray());
    $Title->changed_data = $changedData;
    $Title->save();

      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');
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
    $Title->admin_id = auth()->id();
    $Title->save();

    $changedData = $this->getChangedData($oldData, $Title->toArray());
    $Title->changed_data = $changedData;
    $Title->save();


      return response()->json([
          'data' => new TitleResource($Title),
          'message' => 'Title has been notActive.'
      ]);
  }
}
