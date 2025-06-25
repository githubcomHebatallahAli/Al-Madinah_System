<?php

namespace App\Http\Controllers\Admin;

use App\Models\Hotel;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HotelRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\HotelResource;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ShowAllHotelResource;


class HotelController extends Controller
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

    $query = Hotel::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('place') && in_array($request->place, ['Mecca', 'Almadinah'])) {
        $query->where('place', $request->place);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $Hotels = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllHotelResource::collection($Hotels),
        'pagination' => [
            'total' => $Hotels->total(),
            'count' => $Hotels->count(),
            'per_page' => $Hotels->perPage(),
            'current_page' => $Hotels->currentPage(),
            'total_pages' => $Hotels->lastPage(),
            'next_page_url' => $Hotels->nextPageUrl(),
            'prev_page_url' => $Hotels->previousPageUrl(),
        ],
        'message' => "Show All Hotels."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = Hotel::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('place') && in_array($request->place, ['Mecca', 'Almadinah'])) {
        $query->where('place', $request->place);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $Hotels = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllHotelResource::collection($Hotels),
        'message' => "Show All Hotels."
    ]);
}



    public function create(HotelRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'service_id','company_id', 'name','place','address','bedPrice',
            'description','communication','quantity','sellingPrice','purchesPrice','roomType',
            'rentalStart','rentalEnd','rentalStartHijri','rentalEndHijri',
        ]), $this->prepareCreationMetaData());

              if (isset($data['sellingPrice']) && isset($data['purchesPrice'])) {
        $data['profit'] = $data['sellingPrice'] - $data['purchesPrice'];
    }

        $Hotel = Hotel::create($data);

         return $this->respondWithResource($Hotel, "Hotel created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Hotel = Hotel::find($id);

        if (!$Hotel) {
            return response()->json([
                'message' => "Hotel not found."
            ], 404);
            }

    return $this->respondWithResource($Hotel, "Hotel retrieved for editing.");
        }

public function update(HotelRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Hotel = Hotel::findOrFail($id);
    $oldData = $Hotel->toArray();

    $updateData = $request->only(['status', 'service_id','company_id','name','place','address',
            'description','communication','quantity','sellingPrice','purchesPrice','bedPrice',
            'roomType','rentalStart','rentalEnd','rentalStartHijri','rentalEndHijri'



            ]);

                     if (isset($updateData['sellingPrice']) || isset($updateData['purchesPrice'])) {
        $sellingPrice = $updateData['sellingPrice'] ?? $Hotel->sellingPrice;
        $purchesPrice = $updateData['purchesPrice'] ?? $Hotel->purchesPrice;
        $updateData['profit'] = $sellingPrice - $purchesPrice;
    }

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Hotel->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Hotel->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Hotel);
        return $this->respondWithResource($Hotel, "لا يوجد تغييرات فعلية");
    }

    $Hotel->update($updateData);
    $changedData = $Hotel->getChangedData($oldData, $Hotel->fresh()->toArray());
    $Hotel->changed_data = $changedData;
    $Hotel->save();

    $this->loadCommonRelations($Hotel);
    return $this->respondWithResource($Hotel, "تم تحديث الفندق بنجاح");
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Hotel = Hotel::findOrFail($id);

        return $this->changeStatusSimple($Hotel, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Hotel = Hotel::findOrFail($id);

        return $this->changeStatusSimple($Hotel, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return HotelResource::class;
    }
}
