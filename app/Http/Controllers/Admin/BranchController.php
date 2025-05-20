<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Http\Resources\Admin\BranchResource;

class BranchController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;

        public function showAll()
    {
        $this->authorize('manage_users');
        $Branch = Branch::orderBy('created_at', 'desc')
        ->get();

                  return response()->json([
                      'data' =>  BranchResource::collection($Branch),
                      'message' => "Show All branches."
                  ]);
    }


    public function create(BranchRequest $request)
    {
        $this->authorize('manage_users');
           $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

        $Branch = Branch::create([
            'city_id'=> $request ->city_id,
            "name" => $request->name,
            "address" => $request-> address,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
        ]);
           return response()->json([
            'data' =>new BranchResource($Branch),
            'message' => "Branch Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');

        $Branch = Branch::with(['titles','offices','trips','stores'])
        ->find($id);


            if (!$Branch) {
                return response()->json([
                    'message' => "Branch not found."
                ], 404);
            }

            return response()->json([
                'data' => new BranchResource($Branch),
                'message' => "Edit Branch By ID Successfully."
            ]);
        }

        public function update(BranchRequest $request, string $id)
        {
          $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $Branch =Branch::findOrFail($id);
        $oldData = $Branch->toArray();

           if (!$Branch) {
            return response()->json([
                'message' => "Branch not found."
            ], 404);
        }
           $Branch->update([
           'city_id'=> $request ->city_id,
            "name" => $request->name,
            "address" => $request-> address,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            ]);

        $changedData = $this->getChangedData($oldData, $Branch->toArray());
        $Branch->changed_data = $changedData;

           $Branch->save();
           return response()->json([
            'data' =>new BranchResource($Branch),
            'message' => " Update Branch By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $this->authorize('manage_users');
      $Branch =Branch::findOrFail($id);

      if (!$Branch) {
       return response()->json([
           'message' => "Branch not found."
       ]);
   }
       $oldData = $Branch->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Branch->status = 'active';
    $Branch->creationDate = $creationDate;
    $Branch->creationDateHijri = $hijriDate;
    $Branch->admin_id = auth()->id();
    $Branch->save();

    $changedData = $this->getChangedData($oldData, $Branch->toArray());
    $Branch->changed_data = $changedData;
    $Branch->save();

      return response()->json([
          'data' => new BranchResource($Branch),
          'message' => 'Branch has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');
      $Branch =Branch::findOrFail($id);

      if (!$Branch) {
       return response()->json([
           'message' => "Branch not found."
       ]);
   }

          $oldData = $Branch->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Branch->status = 'notActive';
    $Branch->creationDate = $creationDate;
    $Branch->creationDateHijri = $hijriDate;
    $Branch->admin_id = auth()->id();
    $Branch->save();

    $changedData = $this->getChangedData($oldData, $Branch->toArray());
    $Branch->changed_data = $changedData;
    $Branch->save();


      return response()->json([
          'data' => new BranchResource($Branch),
          'message' => 'Branch has been notActive.'
      ]);
  }
}
