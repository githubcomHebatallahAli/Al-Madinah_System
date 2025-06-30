<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Http\Resources\Admin\PaymentMethodResource;
use App\Http\Resources\Admin\ShowAllPaymentMethodResource;

class PaymentMethodController extends Controller
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

    $query = PaymentMethod::query();

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

        if ($request->filled('search')) {
    $query->where('name', 'like', '%' . $request->search . '%');
}

    $PaymentMethod = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllPaymentMethodResource::collection($PaymentMethod),
        'pagination' => [
            'total' => $PaymentMethod->total(),
            'count' => $PaymentMethod->count(),
            'per_page' => $PaymentMethod->perPage(),
            'current_page' => $PaymentMethod->currentPage(),
            'total_pages' => $PaymentMethod->lastPage(),
            'next_page_url' => $PaymentMethod->nextPageUrl(),
            'prev_page_url' => $PaymentMethod->previousPageUrl(),
        ],
        'message' => "Show All Payment Method."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    // $searchTerm = $request->input('search', '');

    $query = PaymentMethod::query();

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    // if (!empty($searchTerm)) {
    //     $query->where('name', 'like', '%' . $searchTerm . '%');
    // }


        if ($request->filled('search')) {
    $query->where('name', 'like', '%' . $request->search . '%');
}

    $PaymentMethod = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllPaymentMethodResource::collection($PaymentMethod),
        'message' => "Show All Payment Method."
    ]);
}


     public function create(PaymentMethodRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
             'name'

        ]), $this->prepareCreationMetaData());

        $PaymentMethod = PaymentMethod::create($data);

         return $this->respondWithResource($PaymentMethod, "PaymentMethod created successfully.");
    }


public function update(PaymentMethodRequest $request, string $id)
{
    $this->authorize('manage_system');
    $PaymentMethod = PaymentMethod::findOrFail($id);
    $oldData = $PaymentMethod->toArray();

    $updateData = $request->only(['name','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $PaymentMethod->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($PaymentMethod->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($PaymentMethod);
        return $this->respondWithResource($PaymentMethod, "لا يوجد تغييرات فعلية");
    }

    $PaymentMethod->update($updateData);

    $changedData = $PaymentMethod->getChangedData($oldData, $PaymentMethod->fresh()->toArray());
    $PaymentMethod->changed_data = $changedData;
    $PaymentMethod->save();



    $this->loadCommonRelations($PaymentMethod);
    return $this->respondWithResource($PaymentMethod, "تم تحديث وسيله الدفع بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $PaymentMethod = PaymentMethod::find($id);

    if (!$PaymentMethod) {
        return response()->json(['message' => "PaymentMethod not found."], 404);
    }

    return $this->respondWithResource($PaymentMethod, "PaymentMethod retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $PaymentMethod = PaymentMethod::findOrFail($id);

        return $this->changeStatusSimple($PaymentMethod, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $PaymentMethod = PaymentMethod::findOrFail($id);

        return $this->changeStatusSimple($PaymentMethod, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return PaymentMethodResource::class;
    }
}
