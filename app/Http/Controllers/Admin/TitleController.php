<?php

namespace App\Http\Controllers\Admin;

use App\Models\Title;
use Illuminate\Http\Request;


use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesStatusChangeTrait;
use App\Http\Requests\Admin\TitleRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\TitleResource;

class TitleController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesStatusChangeTrait;

    public function showAll()
    {
        $this->authorize('manage_system');
        $titles = Title::orderBy('created_at', 'desc')->get();

        foreach ($titles as $title) {
            $this->loadCreatorRelations($title);
            $this->loadUpdaterRelations($title);
        }

        return response()->json([
            'data' => TitleResource::collection($titles),
            'message' => "All titles retrieved successfully."
        ]);
    }

    public function create(TitleRequest $request)
    {
        $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();

        $title = Title::create([
            'branch_id' => $request->branch_id,
            "name" => $request->name,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
            'updated_by' => $addedById, // يتم تعيينه بنفس added_by
            'updated_by_type' => $addedByType // سيظهر null في الـ Resource
        ]);

        $this->loadCreatorRelations($title);
        $this->loadUpdaterRelations($title);

        return response()->json([
            'data' => new TitleResource($title),
            'message' => "Title created successfully."
        ]);
    }

    public function edit(string $id)
    {
        $this->authorize('manage_system');
        $title = Title::with(['workers'])->find($id);

        if (!$title) {
            return response()->json([
                'message' => "Title not found."
            ], 404);
        }

        $this->loadCreatorRelations($title);
        $this->loadUpdaterRelations($title);

        return response()->json([
            'data' => new TitleResource($title),
            'message' => "Title retrieved for editing."
        ]);
    }


    public function update(TitleRequest $request, string $id)
{
    $this->authorize('manage_system');
    $title = Title::find($id);

    if (!$title) {
        return response()->json([
            'message' => "Title not found."
        ], 404);
    }

    $oldData = $title->toArray();

    $hasChanges = false;
    $fieldsToCheck = ['branch_id', 'name', 'status'];

    foreach ($fieldsToCheck as $field) {
        if ($request->has($field) && $title->$field != $request->$field) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCreatorRelations($title);
        return response()->json([
            'data' => new TitleResource($title),
            'message' => "No actual changes detected."
        ]);
    }

    $hijriDate = $this->getHijriDate();
    $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();

    $title->update([
        'branch_id' => $request->branch_id,
        "name" => $request->name,
        'status' => $request->status ?? 'active',
        'creationDate' => $gregorianDate, // يتم تحديث تاريخ التعديل هنا
        'creationDateHijri' => $hijriDate,
        'updated_by' => $updatedById,
        'updated_by_type' => $updatedByType
    ]);

    $changedData = $this->getChangedData($oldData, $title->toArray());
    $title->changed_data = $changedData;
    $title->save();

    $this->loadCreatorRelations($title);
    $this->loadUpdaterRelations($title);

    return response()->json([
        'data' => new TitleResource($title),
        'message' => "Title updated successfully."
    ]);
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $title = Title::findOrFail($id);

        return $this->changeStatusSimple($title, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $title = Title::findOrFail($id);

        return $this->changeStatusSimple($title, 'notActive');
    }

    // هنا نرجع الريسورس الخاص بالـ Title
    protected function getResourceClass(): string
    {
        return TitleResource::class;
    }
}
