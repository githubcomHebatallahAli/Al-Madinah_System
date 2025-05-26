<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

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


// protected function prepareUpdateMeta($request, $model, ?string $status = null): array
// {
//     $updatedBy = $this->getUpdatedByIdOrFail();

//     return [
//         'updated_by' => $updatedBy,
//         'updated_by_type' => $this->getUpdatedByType(),
//         'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
//         'creationDateHijri' => $this->getHijriDate(),
//         'status' => $request->status ?? $status,
//     ];
// }

protected function prepareUpdateMeta($request, $model, ?string $status = null): array
{
    return [
        'updated_by' => $this->getUpdatedByIdOrFail(),
        'updated_by_type' => $this->getUpdatedByType(),
        'creationDate' => optional($model->creationDate)->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
'creationDateHijri' => $model->creationDateHijri,// مفترض إنها محفوظة مسبقاً
        'status' => $request->status ?? $status,
    ];
}


public function getHijriDateFromDate($date)
{
    if (!$date) {
        return null;
    }

    $date = Carbon::parse($date)->timezone('Asia/Riyadh');

    $response = Http::get('https://api.aladhan.com/v1/gToH', [
        'date' => $date->format('d-m-Y'),
    ]);

    if (!$response->ok() || empty($response['data']['hijri'])) {
        return null;
    }

    $hijri = $response['data']['hijri'];

    return "{$hijri['weekday']['ar']} {$hijri['day']} {$hijri['month']['ar']} {$hijri['year']} - {$date->format('H:i:s')}";
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


    protected function loadCommonRelations($model): void
    {
        if (method_exists($this, 'loadCreatorRelations')) {
            $this->loadCreatorRelations($model);
        }

        if (method_exists($this, 'loadUpdaterRelations')) {
            $this->loadUpdaterRelations($model);
        }
    }


    protected function loadRelationsForCollection(Collection $collection): void
    {
        foreach ($collection as $model) {
            $this->loadCommonRelations($model);
        }
    }

    public function changeStatusSimple($model, string $status): JsonResponse
    {
        $oldStatus = $model->status;

        if ($oldStatus === $status) {
            $this->loadCommonRelations($model);
            return response()->json([
                'data' => new ($this->getResourceClass())($model),
                'message' => $this->getAlreadyStatusMessage($status),
            ]);
        }

        $oldData = $model->toArray();

        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

        $model->update([
            'status' => $status,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'updated_by' => $updatedById,
            'updated_by_type' => $updatedByType,
        ]);

        $changedData = $this->getChangedData($oldData, $model->fresh()->toArray());
        $model->changed_data = $changedData;
        $model->save();

        $this->loadCommonRelations($model);

        return response()->json([
            'data' => new ($this->getResourceClass())($model),
            'message' => $this->getActivatedStatusMessage($status),
        ]);
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
