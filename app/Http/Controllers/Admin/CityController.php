<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Http\Resources\Admin\CityResource;


class CityController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;

        public function showAll()
    {
        $this->authorize('manage_users');
       $City = City::orderBy('created_at', 'desc')
                 ->get();

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

        $City = City::create([
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
            'added_by' => auth('admin')->id(),
        ]);
           return response()->json([
            'data' =>new CityResource($City),
            'message' => "City Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');

       $City = City::with('branches')->find($id);

            if (!$City) {
                return response()->json([
                    'message' => "City not found."
                ], 404);
            }

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
           $City =City::findOrFail($id);
             $oldData = $City->toArray();

           if (!$City) {
            return response()->json([
                'message' => "City not found."
            ], 404);
        }
           $City->update([
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            'added_by' => auth('admin')->id(),

            ]);

        $changedData = $this->getChangedData($oldData, $City->toArray());
        $City->changed_data = $changedData;

           $City->save();
           return response()->json([
            'data' =>new CityResource($City),
            'message' => " Update City By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $this->authorize('manage_users');
      $City =City::findOrFail($id);

      if (!$City) {
       return response()->json([
           'message' => "City not found."
       ]);
   }
       $oldData = $City->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $City->status = 'active';
    $City->creationDate = $creationDate;
    $City->creationDateHijri = $hijriDate;
    $City->added_by = auth('admin')->id();
    $City->save();

    $changedData = $this->getChangedData($oldData, $City->toArray());
    $City->changed_data = $changedData;
    $City->save();

      return response()->json([
          'data' => new CityResource($City),
          'message' => 'City has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');
      $City =City::findOrFail($id);

      if (!$City) {
       return response()->json([
           'message' => "City not found."
       ]);
   }

          $oldData = $City->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $City->status = 'notActive';
    $City->creationDate = $creationDate;
    $City->creationDateHijri = $hijriDate;
    $City->added_by = auth('admin')->id();
    $City->save();

    $changedData = $this->getChangedData($oldData, $City->toArray());
    $City->changed_data = $changedData;
    $City->save();


      return response()->json([
          'data' => new CityResource($City),
          'message' => 'City has been notActive.'
      ]);
  }
}
