<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HandlesStatusChangeTrait
{
    public function changeStatusSimple($model, string $status): JsonResponse
    {
        $oldStatus = $model->status;

        if ($oldStatus === $status) {
            $this->loadCreatorRelations($model);
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

        $this->loadCreatorRelations($model);
        $this->loadUpdaterRelations($model);

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
