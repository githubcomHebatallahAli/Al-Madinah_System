<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bus;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BusRequest;
use App\Http\Resources\Admin\BusResource;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ShowAllBusResource;

class BusController extends Controller
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

    $query = Bus::query();

    if ($request->filled('seatNum')) {
        $query->where('seatNum', $request->seatNum);
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $buses = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllBusResource::collection($buses),
        'pagination' => [
            'total' => $buses->total(),
            'count' => $buses->count(),
            'per_page' => $buses->perPage(),
            'current_page' => $buses->currentPage(),
            'total_pages' => $buses->lastPage(),
            'next_page_url' => $buses->nextPageUrl(),
            'prev_page_url' => $buses->previousPageUrl(),
        ],
        'message' => "Show All Buses."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = Bus::query();

    if ($request->filled('seatNum')) {
        $query->where('seatNum', $request->seatNum);
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $buses = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllBusResource::collection($buses),
        'message' => "Show All Buses."
    ]);
}



        public function create(BusRequest $request)
    {
        $this->authorize('manage_system');

        $data = array_merge($request->only([
            'service_id','company_id', 'busNum', 'busModel', 'plateNum',
            'seatNum','seatPrice', 'quantity', 'sellingPrice', 'purchesPrice',
            'seatMap','rentalStart','rentalEnd','rentalStartHijri','rentalEndHijri',
        ]), $this->prepareCreationMetaData());

         if (isset($data['sellingPrice']) && isset($data['purchesPrice'])) {
        $data['profit'] = $data['sellingPrice'] - $data['purchesPrice'];
    }

        $Bus = Bus::create($data);

        if (!isset($data['seatMap'])) {
            $Bus->generateDefaultSeatMap();
        }

        return $this->respondWithResource($Bus, "Bus created successfully.");
    }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Bus = Bus::find($id);

        if (!$Bus) {
            return response()->json([
                'message' => "Bus not found."
            ], 404);
            }

    return $this->respondWithResource($Bus, "Bus retrieved for editing.");
        }

    public function update(BusRequest $request, string $id)
    {
        $this->authorize('manage_system');
        $Bus = Bus::findOrFail($id);
        $oldData = $Bus->toArray();

        $updateData = $request->only([
            'status', 'service_id','company_id', 'busNum', 'busModel', 'plateNum',
            'seatNum','seatPrice', 'quantity', 'sellingPrice', 'purchesPrice',
            'seatMap','rentalStart','rentalEnd','rentalStartHijri','rentalEndHijri',
        ]);

            if (isset($updateData['sellingPrice']) || isset($updateData['purchesPrice'])) {
        $sellingPrice = $updateData['sellingPrice'] ?? $Bus->sellingPrice;
        $purchesPrice = $updateData['purchesPrice'] ?? $Bus->purchesPrice;
        $updateData['profit'] = $sellingPrice - $purchesPrice;
    }


        if ($request->has('seatNum') && $Bus->seatNum != $request->seatNum) {
            $Bus->seatNum = $request->seatNum;
            $Bus->generateDefaultSeatMap();
        }

        elseif ($request->has('seatMap')) {
            $Bus->seatMap = $request->seatMap;
        }

        $updateData = array_merge(
            $updateData,
            $this->prepareUpdateMeta($request, $Bus->status)
        );

        $hasChanges = false;
        foreach ($updateData as $key => $value) {
            if ($Bus->$key != $value) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            $this->loadCommonRelations($Bus);
            return $this->respondWithResource($Bus, "لا يوجد تغييرات فعلية");
        }

        $Bus->update($updateData);
        $changedData = $Bus->getChangedData($oldData, $Bus->fresh()->toArray());
        $Bus->changed_data = $changedData;
        $Bus->save();

        $this->loadCommonRelations($Bus);
        return $this->respondWithResource($Bus, "تم تحديث الباص بنجاح");
    }


  public function updateSeatMap(BusRequest $request, string $id)
    {
        $this->authorize('manage_system');
        $Bus = Bus::findOrFail($id);

        $Bus->seatMap = $request->seatMap;
        $Bus->save();

        return $this->respondWithResource($Bus, "تم تحديث خريطة المقاعد بنجاح");
    }

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Bus = Bus::findOrFail($id);

        return $this->changeStatusSimple($Bus, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Bus = Bus::findOrFail($id);

        return $this->changeStatusSimple($Bus, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return BusResource::class;
    }
}
