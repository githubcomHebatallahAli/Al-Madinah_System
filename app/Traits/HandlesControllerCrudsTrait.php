<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;


trait HandlesControllerCrudsTrait
{

protected function respondWithResource($model, ?string $message = null): JsonResponse
{
    $this->loadCommonRelations($model);

    return response()->json([
        'data' => new ($this->getResourceClass())($model),
        'message' => $message ?? 'Operation successful.',
    ]);
}


protected function respondWithCollection(Collection $collection, ?string $message = null): JsonResponse
{
    $this->loadRelationsForCollection($collection);

    return response()->json([
        'data' => $this->getResourceClass()::collection($collection),
        'message' => $message ?? 'Data fetched successfully.',
    ]);
}

    protected function prepareCreationMetaData(): array
    {
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();

        return [
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
        ];
    }


protected function prepareUpdateMeta($request,?string $status = null): array
{
    $updatedBy = $this->getUpdatedByIdOrFail();

    return [
        'updated_by' => $updatedBy,
        'updated_by_type' => $this->getUpdatedByType(),
        'status' => $request->status ?? $status,
    ];
}

protected function mergeWithOld($request, $model, array $fields): array
{
    $result = [];

    foreach ($fields as $field) {
        $result[$field] = $request->has($field) ? $request->$field : $model->$field;
    }

    return $result;
}

    protected function applyChangesAndSave($model, array $data, array $oldData): void
    {
        $model->update($data);
        $changedData = $this->getChangedData($oldData, $model->fresh()->toArray());
        $model->changed_data = $changedData;
        $model->save();
    }


    // protected function loadCommonRelations($model): void
    // {
    //     if (method_exists($this, 'loadCreatorRelations')) {
    //         $this->loadCreatorRelations($model);
    //     }

    //     if (method_exists($this, 'loadUpdaterRelations')) {
    //         $this->loadUpdaterRelations($model);
    //     }
    // }


    // protected function loadRelationsForCollection(Collection $collection): void
    // {
    //     foreach ($collection as $model) {
    //         $this->loadCommonRelations($model);
    //     }
    // }

    protected function loadRelationsForCollection($collection): void
{
    // تحويل Paginator إلى Collection إذا لزم الأمر
    $items = $collection instanceof LengthAwarePaginator
        ? $collection->getCollection()
        : $collection;

    foreach ($items as $model) {
        $this->loadCommonRelations($model);
    }

    // إعادة تعيين Collection لل Paginator إذا كان paginated
    if ($collection instanceof LengthAwarePaginator) {
        $collection->setCollection($items);
    }
}


    protected function changeStatusSimple($model, string $newStatus)
{
    $oldData = $model->toArray();

    if ($model->status === $newStatus) {
        $this->loadCommonRelations($model);
        return $this->respondWithResource($model, 'لم يحدث أي تغيير');
    }

    $model->status = $newStatus;
    $model->save();

    $metaForDiffOnly = [
        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        'creationDateHijri' => $this->getHijriDate(),
    ];

    $changedData = $model->getChangedData($oldData, array_merge($model->fresh()->toArray(), $metaForDiffOnly));
    $model->changed_data = $changedData;
    $model->save();

    $this->loadCommonRelations($model);
    return $this->respondWithResource($model, 'تم تغيير الحالة بنجاح');
}


    protected function getActivatedStatusMessage(string $status): string
    {
        return match ($status) {
            'active' => 'Title has been activated.',
            'notActive' => 'Title has been deactivated.',
            default => 'Status has been updated.',
        };
    }

    protected function getAlreadyStatusMessage(string $status): string
    {
        return match ($status) {
            'active' => 'Title is already active.',
            'notActive' => 'Title is already inactive.',
            default => 'Status is already set.',
        };
    }

    abstract protected function getResourceClass(): string;
}
