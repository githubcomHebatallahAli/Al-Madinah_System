<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pilgrim;
use App\Models\MainInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Models\PaymentMethodType;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Traits\HandlesInvoiceStatusChangeTrait;
use App\Http\Resources\Admin\MainInvoiceResource;

class MainInvoiceController extends Controller
{
    use HandlesInvoiceStatusChangeTrait;
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

    protected function ensureNumeric($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    return is_numeric($value) ? $value : 0;
}

protected function prepareUpdateMetaData(): array
{
    $updatedBy = $this->getUpdatedByIdOrFail();
    return [
        'updated_by' => $updatedBy,
        'updated_by_type' => $this->getUpdatedByType(),
        'updated_at' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        'updated_at_hijri' => $this->getHijriDate(),
    ];
}


protected function getPivotChanges(array $oldPivotData, array $newPivotData): array
{
    $changes = [];

    foreach (array_diff_key($oldPivotData, $newPivotData) as $pilgrimId => $pivot) {
        $changes[$pilgrimId] = [
            'old' => $pivot,
            'new' => null,
        ];
    }

    foreach (array_diff_key($newPivotData, $oldPivotData) as $pilgrimId => $pivot) {
        $changes[$pilgrimId] = [
            'old' => null,
            'new' => $pivot,
        ];
    }

    foreach ($newPivotData as $pilgrimId => $newPivot) {
        if (!isset($oldPivotData[$pilgrimId])) continue;

        $oldPivot = $oldPivotData[$pilgrimId];
        $diffOld = [];
        $diffNew = [];

        foreach ($newPivot as $key => $value) {
            if (!array_key_exists($key, $oldPivot)) continue;

            if ($oldPivot[$key] != $value) {
                $diffOld[$key] = $oldPivot[$key];
                $diffNew[$key] = $value;
            }
        }

        if (!empty($diffOld)) {
            $changes[$pilgrimId] = [
                'old' => $diffOld,
                'new' => $diffNew,
            ];
        }
    }

    return $changes;
}

protected function syncPilgrims(MainInvoice $invoice, array $pilgrims)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    // 🟠 حفظ بيانات البفوت القديمة قبل التعديل
    $oldPivotPilgrims = $invoice->pilgrims->mapWithKeys(function ($pilgrim) {
        return [
            $pilgrim->id => [
                'creationDate' => $pilgrim->pivot->creationDate,
                'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
            ],
        ];
    })->toArray();

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);
        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $p->id)->first();

        $pilgrimsData[$p->id] = [
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null, // هيتحدث لاحقًا
        ];
    }

    // 🟢 تنفيذ منطق التغيير وتسجيل الفرق
    $pivotChanges = $this->getPivotChanges($oldPivotPilgrims, $pilgrimsData);

    foreach ($pivotChanges as $pilgrimId => $change) {
        if (isset($pilgrimsData[$pilgrimId])) {
            $pilgrimsData[$pilgrimId]['changed_data'] = json_encode($change, JSON_UNESCAPED_UNICODE);
        }
    }

    $invoice->pilgrims()->sync($pilgrimsData);
}

protected function hasPilgrimsChanges(MainInvoice $invoice, array $newPilgrims): bool
{
    $currentPilgrims = $invoice->pilgrims()->pluck('pilgrims.id')->toArray();

    $newPilgrimsIds = [];
    foreach ($newPilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);
        $newPilgrimsIds[] = $p->id;
    }

    sort($currentPilgrims);
    sort($newPilgrimsIds);

    return $currentPilgrims !== $newPilgrimsIds;
}

protected function attachPilgrims(MainInvoice $invoice, array $pilgrims)
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

        $pilgrimsData[$p->id] = [
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->attach($pilgrimsData);
}

protected function findOrCreatePilgrimForInvoice(array $pilgrimData): Pilgrim
{
    if (empty($pilgrimData['idNum'])) {
        throw new \Exception('رقم الهوية (idNum) مطلوب لكل معتمر بما فيهم الأطفال.');
    }

    $pilgrim = Pilgrim::where('idNum', $pilgrimData['idNum'])->first();

    if (!$pilgrim) {
        if (!isset($pilgrimData['name'], $pilgrimData['nationality'], $pilgrimData['gender'])) {
            throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع على الأقل');
        }

        return Pilgrim::create([
            'idNum'        => $pilgrimData['idNum'],
            'name'         => $pilgrimData['name'],
            'nationality'  => $pilgrimData['nationality'],
            'gender'       => $pilgrimData['gender'],
            'phoNum'       => $pilgrimData['phoNum'] ?? null
        ]);
    }

    $updates = [];
    if (!empty($pilgrimData['name']) && $pilgrim->name !== $pilgrimData['name']) {
        $updates['name'] = $pilgrimData['name'];
    }
    if (!empty($pilgrimData['nationality']) && $pilgrim->nationality !== $pilgrimData['nationality']) {
        $updates['nationality'] = $pilgrimData['nationality'];
    }
    if (!empty($pilgrimData['gender']) && $pilgrim->gender !== $pilgrimData['gender']) {
        $updates['gender'] = $pilgrimData['gender'];
    }
    if (!empty($pilgrimData['phoNum']) && $pilgrim->phoNum !== $pilgrimData['phoNum']) {
        $updates['phoNum'] = $pilgrimData['phoNum'];
    }

    if (!empty($updates)) {
        $pilgrim->update($updates);
    }

    return $pilgrim;
}

 public function pending($id)
    {
        $invoice = MainInvoice::find($id);
        return $this->changeInvoiceStatus($invoice, 'pending');
    }

   public function absence($id, Request $request)
    {
        $invoice = MainInvoice::find($id);
        return $this->changeInvoiceStatus($invoice, 'absence', [
            'reason' => $request->input('reason'),
        ]);
    }

    public function approved($id)
    {
        $invoice = MainInvoice::find($id);
        return $this->changeInvoiceStatus($invoice, 'approved');
    }

    public function rejected($id, Request $request)
    {
        $invoice = MainInvoice::find($id);
        return $this->changeInvoiceStatus($invoice, 'rejected', [
            'reason' => $request->input('reason'),
        ]);
    }

    public function completed($id, Request $request)
    {
        $validated = $request->validate([
            'payment_method_type_id' => 'required|exists:payment_method_types,id',
            'paidAmount' => 'required|numeric|min:0|max:99999.99',
            'discount' => 'nullable|numeric|min:0|max:99999.99',
            'tax' => 'nullable|numeric|min:0|max:99999.99'
        ]);

        $invoice = MainInvoice::find($id);
        return $this->changeInvoiceStatus($invoice, 'completed', $validated);
    }


        protected function getResourceClass(): string
    {
        return MainInvoiceResource::class;
    }

}
