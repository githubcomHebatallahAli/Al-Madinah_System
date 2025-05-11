<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use Illuminate\Http\Request;
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
        // $this->authorize('showAll',City::class);
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
            'creationDate' => $gregorianDate,  // ميلادي مع الوقت
            'creationDateHijri' => $hijriDate,  // هجري مع الوقت
            'admin_id' => auth()->id(),
            'status' => 'active',
        ]);
           return response()->json([
            'data' =>new CityResource($City),
            'message' => "City Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');

        $City = City::withCount('branches')
        ->with('branches')->find($id);

            if (!$City) {
                return response()->json([
                    'message' => "City not found."
                ], 404);
            }

            // $this->authorize('edit',$City);

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
            'creationDate' => $gregorianDate,  // ميلادي مع الوقت
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status,
            'admin_id' => auth()->id(),

            ]);

            $changedData = $this->getChangedData($oldData, $City->toArray());
        $City->changed_data = json_encode($changedData);

           $City->save();
           return response()->json([
            'data' =>new CityResource($City),
            'message' => " Update City By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $City =City::findOrFail($id);

      if (!$City) {
       return response()->json([
           'message' => "City not found."
       ]);
   }
    $this->authorize('active',$City);

      $City->update(['status' => 'active']);

      return response()->json([
          'data' => new CityResource($City),
          'message' => 'City has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $City =City::findOrFail($id);

      if (!$City) {
       return response()->json([
           'message' => "City not found."
       ]);
   }
    $this->authorize('notActive',$City);

      $City->update(['status' => 'notActive']);

      return response()->json([
          'data' => new CityResource($City),
          'message' => 'City has been notActive.'
      ]);
  }
}
