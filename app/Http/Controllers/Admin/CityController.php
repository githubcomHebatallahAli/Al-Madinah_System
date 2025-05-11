<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Http\Resources\Admin\CityResource;

class CityController extends Controller
{
    use HijriDateTrait;

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
           $City =City::create ([
                "name" => $request->name,
                'creationDate' => $hijriDate,
                'admin_id' => auth()->id(),
                'status'=>'active',
            ]);

           return response()->json([
            'data' =>new CityResource($City),
            'message' => "City Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');

        $City = City::withCount('details')
        ->with('details')->find($id);

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
           $City =City::findOrFail($id);

           if (!$City) {
            return response()->json([
                'message' => "City not found."
            ], 404);
        }
           $City->update([
            "name" => $request->name,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            'status'=> $request-> status,
            'admin_id' => auth()->id(),

            ]);

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
