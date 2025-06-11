<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bus;
use App\Models\BusTrip;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Requests\Admin\BusTripRequest;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\BusTripResource;
use App\Http\Resources\Admin\ShowAllBusTripResource;


class BusTripController extends Controller
{
     use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

public function showAllWithPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = BusTrip::query();
    if ($request->filled('bus_id')) {
        $query->where('bus_id', $request->bus_id);
    }

    $BusTrips = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllBusTripResource::collection($BusTrips),
        'pagination' => [
            'total' => $BusTrips->total(),
            'count' => $BusTrips->count(),
            'per_page' => $BusTrips->perPage(),
            'current_page' => $BusTrips->currentPage(),
            'total_pages' => $BusTrips->lastPage(),
            'next_page_url' => $BusTrips->nextPageUrl(),
            'prev_page_url' => $BusTrips->previousPageUrl(),
        ],
        'message' => "Show All Bus Drivers."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');
     $query = BusTrip::query();

    if ($request->filled('bus_id')) {
        $query->where('bus_id', $request->bus_id);
    }

    $BusTrips = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllBusTripResource::collection($BusTrips),
        'message' => "Show All Bus Trips."
    ]);
}


     public function create(BusTripRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
            'bus_id', 'trip_id','bus_driver_id','travelDate','travelDateHijri'

        ]), $this->prepareCreationMetaData());

           $bus = Bus::findOrFail($request->bus_id);
    $data['seatMap'] = $bus->seatMap ?? [];

        $BusTrip = BusTrip::create($data);

         return $this->respondWithResource($BusTrip, "BusTrip created successfully.");
    }


public function update(BusTripRequest $request, string $id)
{
    $this->authorize('manage_system');
    $BusTrip = BusTrip::findOrFail($id);
    $oldData = $BusTrip->toArray();

    $updateData = $request->only(['bus_id','trip_id','bus_driver_id','travelDate','travelDateHijri']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $BusTrip->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($BusTrip->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($BusTrip);
        return $this->respondWithResource($BusTrip, "لا يوجد تغييرات فعلية");
    }

    $BusTrip->update($updateData);

    $changedData = $BusTrip->getChangedData($oldData, $BusTrip->fresh()->toArray());
    $BusTrip->changed_data = $changedData;
    $BusTrip->save();



    $this->loadCommonRelations($BusTrip);
    return $this->respondWithResource($BusTrip, "تم تحديث رحله الباص بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $BusTrip = BusTrip::find($id);

    if (!$BusTrip) {
        return response()->json(['message' => "BusTrip not found."], 404);
    }

    return $this->respondWithResource($BusTrip, "BusTrip retrieved for editing.");
}

public function getSeatStats($id)
{
    $this->authorize('manage_system');

    $busTrip = BusTrip::findOrFail($id);

    return response()->json([
        'booked' => $busTrip->bookedSeats,
        'available' => $busTrip->availableSeats,
        'cancelled' => $busTrip->cancelledSeats,
        'total' => is_array($busTrip->seatMap) ? count($busTrip->seatMap) : 0,
    ]);
}

// public function updateSeatStatus(Request $request, $busTripId)
// {
//     $this->authorize('manage_system');

//     $request->validate([
//         'seatNumber' => 'required',
//         'status' => 'required|in:available,booked,cancelled',
//     ]);

//     $busTrip = BusTrip::findOrFail($busTripId);
//     $seatMap = $busTrip->seatMap ?? [];

//     $updated = false;

//     foreach ($seatMap as &$seat) {
//         if (isset($seat['number']) && $seat['number'] == $request->seatNumber) {
//             $seat['status'] = $request->status;
//             $updated = true;
//             break;
//         }
//     }

//     if (!$updated) {
//         return response()->json(['message' => 'Seat not found.'], 404);
//     }

//     $busTrip->seatMap = $seatMap;
//     $busTrip->save();

//     return $this->respondWithResource($busTrip, 'تم تحديث حالة المقعد بنجاح.');
// }


    protected function getResourceClass(): string
    {
        return BusTripResource::class;
    }
}
