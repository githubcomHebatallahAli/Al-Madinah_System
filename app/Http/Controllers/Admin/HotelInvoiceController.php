<?php

namespace App\Http\Controllers\Admin;


use App\Models\Pilgrim;
use App\Models\BusInvoice;
use App\Models\HotelInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\HotelInvoiceRequest;
use App\Http\Resources\Admin\HotelInvoiceResource;


class HotelInvoiceController extends Controller
{
     use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;
    public function showAllWithoutPaginate(Request $request)
    {
        $query = HotelInvoice::with(['hotel', 'trip', 'busInvoice', 'paymentMethodType']);

        // الفلترة
        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('status')) {
            $query->where('invoiceStatus', $request->status);
        }

        $invoices = $query->latest()->paginate(10);

        return response()->json([
            'data' => HotelInvoiceResource::collection($invoices),
            'message' => 'تم جلب الفواتير بنجاح'
        ]);
    }

public function create(HotelInvoiceRequest $request)
{
    $this->authorize('manage_system');

    $data = array_merge([
        'discount' => $this->ensureNumeric($request->input('discount', 0)),
        'tax' => $this->ensureNumeric($request->input('tax', 0)),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
        'subtotal' => 0,
        'total' => 0,
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        $invoice = HotelInvoice::create($data);


        if ($request->has('pilgrims')) {
            $this->attachPilgrims($invoice, $request->pilgrims);
        }

        if ($request->filled('bus_invoice_id')) {
            $this->attachBusPilgrims($invoice, $request->bus_invoice_id);
        }

        $invoice->PilgrimsCount();
        $invoice->calculateTotal();
        DB::commit();

        return $this->respondWithResource(
            new HotelInvoiceResource($invoice->load(['hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])),
            'تم إنشاء فاتورة الفندق بنجاح'
        );

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}


protected function ensureNumeric($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    return is_numeric($value) ? $value : 0;
}

    public function edit(HotelInvoice $id)
    {
        return response()->json([
            'data' => new HotelInvoiceResource($id->load([
                'hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'
            ])),
            'message' => 'تم جلب الفاتورة بنجاح'
        ]);
    }

public function update(HotelInvoiceRequest $request, HotelInvoice $hotelInvoice)
{
    $this->authorize('manage_system');

    $oldData = $hotelInvoice->toArray();
    $oldPilgrimsData = $hotelInvoice->pilgrims()->get()->mapWithKeys(function ($pilgrim) {
        return [
            $pilgrim->id => [
                'creationDate' => $pilgrim->pivot->creationDate,
                'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
            ]
        ];
    })->toArray();

    DB::beginTransaction();
    try {
        $hasChanges = false;
        $updateData = $request->validated();

        foreach ($updateData as $key => $value) {
            if ($hotelInvoice->$key != $value) {
                $hasChanges = true;
                break;
            }
        }

        $pilgrimsChanged = false;
        $newPilgrimsData = [];
        if ($request->has('pilgrims')) {
            $pilgrimsChanged = $this->hasPilgrimsChanges($hotelInvoice, $request->pilgrims);
            $hasChanges = $hasChanges || $pilgrimsChanged;
        }

        if (!$hasChanges) {
            DB::commit();
            return response()->json([
                'data' => new HotelInvoiceResource($hotelInvoice->load(['hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])),
                'message' => 'لا يوجد تغييرات فعلية'
            ]);
        }


        $hotelInvoice->update(array_merge(
            $updateData,
            $this->prepareUpdateMetaData()
        ));


        if ($request->has('pilgrims') && $pilgrimsChanged) {
            $this->syncPilgrims($hotelInvoice, $request->pilgrims);
            $newPilgrimsData = $hotelInvoice->fresh()->pilgrims()->get()->mapWithKeys(function ($pilgrim) {
                return [
                    $pilgrim->id => [

                        'creationDate' => $pilgrim->pivot->creationDate,
                        'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
                    ]
                ];
            })->toArray();
        }
        $hotelInvoice->PilgrimsCount();
        $hotelInvoice->calculateTotal();



        $changedData = $hotelInvoice->getChangedData($oldData, $hotelInvoice->fresh()->toArray());

        if ($pilgrimsChanged) {
            $changedData['pilgrims'] = $this->getPivotChanges($oldPilgrimsData, $newPilgrimsData);
        }

        if (!empty($changedData)) {
            $hotelInvoice->changed_data = $changedData;
            $hotelInvoice->save();
        }

        DB::commit();

        return response()->json([
            'data' => new HotelInvoiceResource($hotelInvoice->load(['hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])),
            'message' => 'تم تحديث الفاتورة بنجاح'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
        ], 500);
    }
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




    public function approve(HotelInvoice $hotelInvoice)
    {
        $hotelInvoice->update(['invoiceStatus' => 'approved']);
        $hotelInvoice->calculateTotal();

        return response()->json([
            'message' => 'تم اعتماد الفاتورة بنجاح'
        ]);
    }

    public function reject(HotelInvoice $hotelInvoice)
    {
        $hotelInvoice->update([
            'invoiceStatus' => 'rejected',
            'subtotal' => 0,
            'total' => 0,
            'paidAmount' => 0
        ]);

        return response()->json([
            'message' => 'تم رفض الفاتورة بنجاح'
        ]);
    }




        protected function getResourceClass(): string
    {
        return HotelInvoiceResource::class;
    }


    protected function attachPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        // التحقق من البيانات المطلوبة للحجاج الجدد
        if (!Pilgrim::where('idNum', $pilgrim['idNum'])->exists()) {
            if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع');
            }
        }

        // إنشاء أو جلب الحاج
        $p = Pilgrim::firstOrCreate(
            ['idNum' => $pilgrim['idNum']],
            [
                'name' => $pilgrim['name'] ?? null,
                'nationality' => $pilgrim['nationality'] ?? null,
                'gender' => $pilgrim['gender'] ?? null,
                'phoNum' => $pilgrim['phoNum'] ?? null
            ]
        );

        // إعداد بيانات الربط (تم إزالة type)
        $pilgrimsData[$p->id] = [
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->attach($pilgrimsData);
}

protected function attachBusPilgrims(HotelInvoice $invoice, $busInvoiceId)
{
    if (empty($busInvoiceId)) {
        return;
    }

    $busInvoice = BusInvoice::with('pilgrims')->find($busInvoiceId);

    if (!$busInvoice) {
        throw new \Exception('عفواً، فاتورة الباص المحددة غير موجودة!');
    }

    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $pilgrimsData = $busInvoice->pilgrims->mapWithKeys(function ($pilgrim) use ($currentDate, $hijriDate) {
        return [
            $pilgrim->id => [
                'creationDate' => $currentDate,
                'creationDateHijri' => $hijriDate,
                'changed_data' => null
            ]
        ];
    });

    $invoice->pilgrims()->attach($pilgrimsData->toArray());
}

protected function syncPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    foreach ($pilgrims as $pilgrim) {
        $existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])->first();

        if (!$existingPilgrim) {
            if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                throw new \Exception('بيانات غير مكتملة للحاج الجديد');
            }

            $existingPilgrim = Pilgrim::create([
                'idNum' => $pilgrim['idNum'],
                'name' => $pilgrim['name'],
                'nationality' => $pilgrim['nationality'],
                'gender' => $pilgrim['gender'],
                'phoNum' => $pilgrim['phoNum'] ?? null
            ]);
        }

        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $existingPilgrim->id)->first();

        $pilgrimsData[$existingPilgrim->id] = [
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->sync($pilgrimsData);
}

protected function hasPilgrimsChanges(HotelInvoice $invoice, array $newPilgrims): bool
{
    $currentPilgrims = $invoice->pilgrims()->pluck('pilgrims.id')->toArray();
    $newPilgrimsIds = collect($newPilgrims)->pluck('idNum')->toArray();

    return count(array_diff($currentPilgrims, $newPilgrimsIds)) > 0 ||
           count(array_diff($newPilgrimsIds, $currentPilgrims)) > 0;
}

protected function preparePilgrimsData(array $pilgrims): array
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        if (!isset($pilgrim['idNum'])) {
            throw new \Exception('بيانات الحاج غير مكتملة');
        }

        $p = Pilgrim::where('idNum', $pilgrim['idNum'])->firstOrFail();

        $pilgrimsData[$p->id] = [
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    return $pilgrimsData;
}
}
