<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;

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


    // ======== تجهيز بيانات الإنشاء المشتركة ========
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
            'updated_by' => $addedById, // = added_by
            'updated_by_type' => $addedByType,
        ];
    }

    // ======== تجهيز بيانات التحديث المشتركة ========
    protected function prepareUpdateMeta($request): array
    {
        return [
            'status' => $request->status ?? 'active',
            'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            'creationDateHijri' => $this->getHijriDate(),
            'updated_by' => $this->getUpdatedByIdOrFail(),
            'updated_by_type' => $this->getUpdatedByType(),
        ];
    }

    // ======== تطبيق التحديثات وحفظ التغييرات ========
    protected function applyChangesAndSave($model, array $data, array $oldData): void
    {
        $model->update($data);
        $changedData = $this->getChangedData($oldData, $model->fresh()->toArray());
        $model->changed_data = $changedData;
        $model->save();
    }

    // ======== تحميل العلاقات للموديل الواحد ========
    protected function loadCommonRelations($model): void
    {
        if (method_exists($this, 'loadCreatorRelations')) {
            $this->loadCreatorRelations($model);
        }

        if (method_exists($this, 'loadUpdaterRelations')) {
            $this->loadUpdaterRelations($model);
        }
    }

    // ======== تحميل العلاقات لمجموعة موديلات ========
    protected function loadRelationsForCollection(Collection $collection): void
    {
        foreach ($collection as $model) {
            $this->loadCommonRelations($model);
        }
    }

    // ======== تغيير حالة الموديل مع رسالة جاهزة ========
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

    // ======== رسائل حالة مفعلة ========
    protected function getActivatedStatusMessage(string $status): string
    {
        return match ($status) {
            'active' => 'Title has been activated.',
            'notActive' => 'Title has been deactivated.',
            default => 'Status has been updated.',
        };
    }

    // ======== رسائل حالة مفعلة مسبقا ========
    protected function getAlreadyStatusMessage(string $status): string
    {
        return match ($status) {
            'active' => 'Title is already active.',
            'notActive' => 'Title is already inactive.',
            default => 'Status is already set.',
        };
    }

    // ======== يجب تعريف هذا في الكنترولر ليخبر التريت بأي Resource يستخدم ========
    abstract protected function getResourceClass(): string;
}
