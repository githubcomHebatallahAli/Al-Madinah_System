<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusTrip;
use App\Models\Pilgrim;
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
use App\Http\Requests\Admin\UpdatePilgrimDataRequest;
use App\Http\Resources\Admin\ShowAllHotelInvoiceResource;

class HotelInvoiceController extends Controller
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

        $query = HotelInvoice::query();

        if ($request->filled('bus_trip_id')) {
            $query->where('bus_trip_id', $request->bus_trip_id);
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }


        if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $HotelInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = HotelInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllHotelInvoiceResource::collection($HotelInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'pagination' => [
                'total' => $HotelInvoices->total(),
                'count' => $HotelInvoices->count(),
                'per_page' => $HotelInvoices->perPage(),
                'current_page' => $HotelInvoices->currentPage(),
                'total_pages' => $HotelInvoices->lastPage(),
                'next_page_url' => $HotelInvoices->nextPageUrl(),
                'prev_page_url' => $HotelInvoices->previousPageUrl(),
            ],
            'message' => "Show All Bus Invoices."
        ]);
    }

    public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = HotelInvoice::query();

        if ($request->filled('bus_trip_id')) {
            $query->where('bus_trip_id', $request->bus_trip_id);
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

          if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }



        $HotelInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = HotelInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllHotelInvoiceResource::collection($HotelInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,

        ],
            'message' => "Show All Bus Invoices."
        ]);
    }

protected function updateSeatStatusInTrip($busTrip, $seatNumber, $status)
{
    // الريفريش ممكن اشيله
    $busTrip->refresh();
    $seatMap = collect($busTrip->seatMap);
    $seatIndex = $seatMap->search(function ($item) use ($seatNumber) {
        return $item['seatNumber'] === $seatNumber;
    });

    if ($seatIndex === false) {
        throw new \Exception("المقعد {$seatNumber} غير موجود في رحلة الباص");
    }

    $updatedSeatMap = $seatMap->all();
    $updatedSeatMap[$seatIndex]['status'] = $status;

    $busTrip->seatMap = $updatedSeatMap;
    $busTrip->save();
}

protected function ensureNumeric($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    return is_numeric($value) ? $value : 0;
}

protected function validateSeatsAvailability(BusTrip $busTrip, array $pilgrims)
{
    // $requestedSeats = collect($pilgrims)->pluck('seatNumber');
    $requestedSeats = collect($pilgrims)->pluck('seatNumber')->flatten();
    $availableSeats = collect($busTrip->seatMap)
        ->where('status', 'available')
        ->pluck('seatNumber');

    $unavailableSeats = $requestedSeats->diff($availableSeats);

    if ($unavailableSeats->isNotEmpty()) {
        throw new \Exception("المقاعد التالية غير متاحة: " . $unavailableSeats->implode(', '));
    }
}

    public function edit(string $id)
    {
        $this->authorize('manage_system');

        $HotelInvoice =HotelInvoice::with([
        'pilgrims',
    ])->find($id);

        if (!$HotelInvoice) {
            return response()->json(['message' => "Bus Invoice not found."], 404);
        }

        return $this->respondWithResource($HotelInvoice, "Bus Invoice retrieved for editing.");
    }




public function update(HotelInvoiceRequest $request, $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::with(['pilgrims', 'busTrip'])->findOrFail($id);
    $oldData = $HotelInvoice->toArray();
    $oldPivot = $HotelInvoice->pilgrims->keyBy('id')->map(fn($p) => $p->pivot->toArray())->toArray();

    $busTrip = $HotelInvoice->busTrip;
    $seatMapArray = $busTrip ? json_decode(json_encode($busTrip->seatMap), true) : [];
    $originalSeats = $HotelInvoice->pilgrims->pluck('pivot.seatNumber')->toArray();

    // التحقق من المقاعد المتاحة
    if ($request->has('pilgrims')) {
        $requestedSeats = collect($request->pilgrims)->pluck('seatNumber')->flatten();
        $availableSeats = collect($seatMapArray)->where('status', 'available')->pluck('seatNumber');
        $availableSeats = $availableSeats->merge($originalSeats)->unique();
        $unavailableSeats = $requestedSeats->diff($availableSeats);

        if ($unavailableSeats->isNotEmpty()) {
            return response()->json([
                'message' => 'بعض المقاعد غير متوفرة',
                'unavailable_seats' => $unavailableSeats
            ], 422);
        }
    }

    $data = array_merge([
        'discount' => $this->ensureNumeric($request->input('discount')),
        'tax' => $this->ensureNumeric($request->input('tax')),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareUpdateMetaData());

    DB::beginTransaction();

    try {

        $HotelInvoice->update($data);

        if ($request->has('pilgrims')) {
            $pilgrimsData = [];
            $incompletePilgrims = $HotelInvoice->incomplete_pilgrims ?? [];

            foreach ($request->pilgrims as $pilgrim) {
                $existingPilgrim = null;


                if (!empty($pilgrim['idNum'])) {
                    $existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])->first();
                } elseif (!empty($pilgrim['phoNum'])) {
                    $existingPilgrim = Pilgrim::where('phoNum', $pilgrim['phoNum'])->first();
                }

                if ($existingPilgrim) {
                    // تحديث بيانات المعتمر الحالي
                    foreach ($pilgrim['seatNumber'] as $seatNumber) {
                        $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

                        if (!$seatInfo) {
                            throw new \Exception("المقعد {$seatNumber} غير موجود");
                        }

                        // تحديث أو إنشاء علاقة جديدة
                        $pilgrimsData[$existingPilgrim->id] = [
                            'seatNumber' => $seatNumber,
                            'status' => 'booked',
                            'type' => $seatInfo['type'] ?? null,
                            'position' => $seatInfo['position'] ?? null,
                            'creationDateHijri' => $this->getHijriDate(),
                            'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                        ];

                        if ($busTrip) {
                            $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                        }
                    }
                } else {
                    // معالجة المعتمرين غير المكتملين
                    if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                        $incompletePilgrims[] = $pilgrim;
                        continue;
                    }

                    // إنشاء معتمر جديد
                    $newPilgrim = Pilgrim::create([
                        'idNum' => $pilgrim['idNum'] ?? null,
                        'name' => $pilgrim['name'],
                        'phoNum' => $pilgrim['phoNum'] ?? null,
                        'nationality' => $pilgrim['nationality'],
                        'gender' => $pilgrim['gender'],
                    ]);

                    foreach ($pilgrim['seatNumber'] as $seatNumber) {
                        $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

                        if (!$seatInfo) {
                            throw new \Exception("المقعد {$seatNumber} غير موجود");
                        }

                        $pilgrimsData[$newPilgrim->id] = [
                            'seatNumber' => $seatNumber,
                            'status' => 'booked',
                            'type' => $seatInfo['type'] ?? null,
                            'position' => $seatInfo['position'] ?? null,
                            'creationDateHijri' => $this->getHijriDate(),
                            'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                        ];

                        if ($busTrip) {
                            $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                        }
                    }
                }
            }

            // الحفاظ على المعتمرين الحاليين وإضافة/تحديث الجدد فقط
            $currentPilgrims = $HotelInvoice->pilgrims->keyBy('id')->map(fn($p) => $p->pivot->toArray())->toArray();
            $finalPilgrimsData = array_merge($currentPilgrims, $pilgrimsData);

            // مزامنة البيانات
            $HotelInvoice->pilgrims()->sync($finalPilgrimsData);
            $HotelInvoice->update(['incomplete_pilgrims' => !empty($incompletePilgrims) ? $incompletePilgrims : null]);
        }

        $HotelInvoice->PilgrimsCount();
        $HotelInvoice->calculateTotal();

        DB::commit();

        return $this->respondWithResource($HotelInvoice->load([
            'pilgrims', 'busTrip', 'campaign', 'office', 'group', 'worker', 'paymentMethodType'
        ]), "تم تحديث فاتورة الباص بنجاح");
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()], 500);
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

protected function checkForChanges($HotelInvoice, $newData, $request): bool
{
    foreach ($newData as $key => $value) {
        if ($HotelInvoice->$key != $value) {
            return true;
        }
    }

    if ($request->has('pilgrims')) {
        $currentPilgrims = $HotelInvoice->pilgrims()->pluck('pilgrims.id')->toArray();
        $newPilgrims = collect($request->pilgrims)->pluck('id')->toArray();

        if (count(array_diff($currentPilgrims, $newPilgrims)) > 0) return true;
        if (count(array_diff($newPilgrims, $currentPilgrims)) > 0) return true;
    }

    return false;
}

protected function preparePilgrimsData($pilgrims, $seatMapArray): array
{
    $pilgrimsData = [];

    foreach ($pilgrims as $pilgrim) {
        if (!isset($pilgrim['id'], $pilgrim['seatNumber'])) {
            throw new \Exception('بيانات الحاج غير مكتملة');
        }

        $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $pilgrim['seatNumber']);

        if (!$seatInfo) {
            throw new \Exception("المقعد {$pilgrim['seatNumber']} غير موجود في seatMap.");
        }

        $pilgrimsData[$pilgrim['id']] = [
            'seatNumber' => $pilgrim['seatNumber'],
            'status' => $pilgrim['status'] ?? 'booked',
            'type' => $seatInfo['type'] ?? null,
            'position' => $seatInfo['position'] ?? null,
            'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            'creationDateHijri' => $this->getHijriDate(),
        ];
    }

    return $pilgrimsData;
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


    // Invoice Status
    public function pending(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->invoiceStatus === 'pending') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to pending');
    }

    $HotelInvoice->invoiceStatus = 'pending';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice set to pending');
}

    public function approved(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->invoiceStatus === 'approved') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to approved');
    }

    $HotelInvoice->invoiceStatus = 'approved';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice set to approved');
}

    public function rejected(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->invoiceStatus === 'rejected') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to rejected');
    }

    $HotelInvoice->invoiceStatus = 'rejected';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice set to rejected');
}

    public function completed(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->invoiceStatus === 'completed') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to completed');
    }

    $HotelInvoice->invoiceStatus = 'completed';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice set to completed');
}

    public function absence(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->invoiceStatus === 'absence') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to absence');
    }

    $HotelInvoice->invoiceStatus = 'absence';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice set to absence');
}

// Payment Status
    public function pendingPayment(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->paymentStatus === 'pending') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to pending');
    }

    $HotelInvoice->paymentStatus = 'pending';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice Payment set to pendind');
}

    public function refuneded(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->paymentStatus === 'refuneded') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice Payment is already set to refuneded');
    }

    $HotelInvoice->paymentStatus = 'refuneded';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice Payment set to refuneded');
}

    public function paid(string $id)
{
    $this->authorize('manage_system');

    $HotelInvoice = HotelInvoice::find($id);
    if (!$HotelInvoice) {
        return response()->json(['message' => "HotelInvoice not found."], 404);
    }

    $oldData = $HotelInvoice->toArray();

    if ($HotelInvoice->paymentStatus === 'paid') {
        $this->loadCommonRelations($HotelInvoice);
        return $this->respondWithResource($HotelInvoice, 'HotelInvoice is already set to paid');
    }

    $HotelInvoice->paymentStatus = 'paid';
    $HotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $HotelInvoice->creationDateHijri = $this->getHijriDate();
    $HotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $HotelInvoice->updated_by_type = $this->getUpdatedByType();
    $HotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $HotelInvoice->creationDate,
        'creationDateHijri' => $HotelInvoice->creationDateHijri,
    ];

    $changedData = $HotelInvoice->getChangedData($oldData, array_merge($HotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $HotelInvoice->changed_data = $changedData;
    $HotelInvoice->save();

    $this->loadCommonRelations($HotelInvoice);
    return $this->respondWithResource($HotelInvoice, 'HotelInvoice set to paid');
}


public function create(HotelInvoiceRequest $request)
{
    $this->authorize('manage_system');

    $busTrip = null;
    $seatMapArray = [];
    $unavailableSeats = collect();

    if ($request->filled('bus_trip_id')) {
        $busTrip = BusTrip::find($request->bus_trip_id);
        if (!$busTrip) {
            return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
        }

        $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

        if ($request->has('pilgrims')) {
            $requestedSeats = collect($request->pilgrims)->pluck('seatNumber')->flatten();
            $availableSeats = collect($seatMapArray)->where('status', 'available')->pluck('seatNumber');
            $unavailableSeats = $requestedSeats->diff($availableSeats);
            if ($unavailableSeats->isNotEmpty()) {
                return response()->json([
                    'message' => 'بعض المقاعد غير متوفرة',
                    'unavailable_seats' => $unavailableSeats
                ], 422);
            }
        }
    }

    $data = array_merge([
        'discount' => $this->ensureNumeric($request->input('discount')),
        'tax' => $this->ensureNumeric($request->input('tax')),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
        'subtotal' => 0,
        'total' => 0,
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

    DB::beginTransaction();

    try {
        $HotelInvoice = HotelInvoice::create($data);
        $pilgrimsData = [];
        $incompletePilgrims = [];

        if ($request->has('pilgrims')) {
            foreach ($request->pilgrims as $pilgrim) {
                $existingPilgrim = null;

                if (!empty($pilgrim['idNum'])) {
                    $existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])->first();
                } elseif (!empty($pilgrim['phoNum'])) {
                    $existingPilgrim = Pilgrim::where('phoNum', $pilgrim['phoNum'])->first();
                }

                if (!$existingPilgrim) {
                    if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                        $incompletePilgrims[] = $pilgrim;
                        continue;
                    }

                    $existingPilgrim = Pilgrim::create([
                        'idNum' => $pilgrim['idNum'] ?? null,
                        'name' => $pilgrim['name'],
                        'phoNum' => $pilgrim['phoNum'] ?? null,
                        'nationality' => $pilgrim['nationality'],
                        'gender' => $pilgrim['gender'],
                    ]);
                }

                foreach ($pilgrim['seatNumber'] as $seatNumber) {
                    $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

                    if (!$seatInfo) {
                        throw new \Exception("المقعد {$seatNumber} غير موجود.");
                    }

                    $pilgrimsData[] = [
                        'pilgrim_id' => $existingPilgrim->id,
                        'seatNumber' => $seatNumber,
                        'status' => 'booked',
                        'type' => $seatInfo['type'] ?? null,
                        'position' => $seatInfo['position'] ?? null,
                        'creationDateHijri' => $this->getHijriDate(),
                        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    ];

                    if ($busTrip) {
                        $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                    }
                }
            }

            if (!empty($pilgrimsData)) {
                $HotelInvoice->pilgrims()->attach($pilgrimsData);
            }
        }

        if (!empty($incompletePilgrims)) {
            $HotelInvoice->update(['incomplete_pilgrims' => $incompletePilgrims]);
        }

        $HotelInvoice->PilgrimsCount();
        $HotelInvoice->calculateTotal();
        DB::commit();

        return response()->json([
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'invoice' => new HotelInvoiceResource($HotelInvoice->load([
                'pilgrims', 'busTrip',  'trip', 'paymentMethodType'
            ])),
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
    }
}

public function updateIncompletePilgrims(UpdatePilgrimDataRequest $request, HotelInvoice $HotelInvoice)
{
    $HotelInvoice = $HotelInvoice->fresh('busTrip');
    $busTrip = $HotelInvoice->busTrip;

    if (!$busTrip) {
        return response()->json(['message' => 'لا توجد رحلة مرتبطة بهذه الفاتورة'], 422);
    }

    $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);
    $pilgrimsData = [];


    $validatedData = $request->validated();

    foreach ($validatedData['pilgrims'] as $pilgrim) {
        $existingPilgrimQuery = Pilgrim::query();

        if (!empty($pilgrim['idNum'])) {
            $existingPilgrimQuery->where('idNum', $pilgrim['idNum']);
        }

        if (!empty($pilgrim['phoNum'])) {
            $existingPilgrimQuery->orWhere('phoNum', $pilgrim['phoNum']);
        }

        $existingPilgrim = $existingPilgrimQuery->first();

        if ($existingPilgrim) {
            $existingPilgrim->update([
                'name'         => $pilgrim['name'],
                'nationality'  => $pilgrim['nationality'],
                'gender'       => $pilgrim['gender'],
                'phoNum'       => $pilgrim['phoNum'] ?? $existingPilgrim->phoNum,
            ]);
        } else {
            $existingPilgrim = Pilgrim::create([
                'idNum'        => $pilgrim['idNum'] ?? null,
                'name'         => $pilgrim['name'],
                'nationality'  => $pilgrim['nationality'],
                'gender'       => $pilgrim['gender'],
                'phoNum'       => $pilgrim['phoNum'] ?? null,
            ]);
        }


        foreach ($pilgrim['seatNumber'] as $seatNumber) {

            $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);
            if (!$seatInfo) {
                return response()->json(["message" => "المقعد $seatNumber غير موجود في seatMap"], 422);
            }


            $alreadyBooked = DB::table('bus_invoice_pilgrims')
                ->where('bus_invoice_id', $HotelInvoice->id)
                ->where('seatNumber', $seatNumber)
                ->exists();
            if ($alreadyBooked) {
                return response()->json(["message" => "المقعد $seatNumber محجوز بالفعل"], 422);
            }

            $pilgrimsData[] = [
                'pilgrim_id'         => $existingPilgrim->id,
                'seatNumber'         => $seatNumber,
                'status'             => 'booked',
                'type'               => $seatInfo['type'] ?? null,
                'position'           => $seatInfo['position'] ?? null,
                'creationDateHijri'  => $this->getHijriDate(),
                'creationDate'       => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            ];

            $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
        }
    }


    $HotelInvoice->pilgrims()->attach($pilgrimsData);

    $HotelInvoice->PilgrimsCount();
    $HotelInvoice->calculateTotal();

    return response()->json([
        'message' => 'تم تحديث بيانات المعتمرين بنجاح',
        'invoice' => new HotelInvoiceResource($HotelInvoice->load(['pilgrims']))
    ]);
}



        protected function getResourceClass(): string
    {
        return HotelInvoiceResource::class;
    }
}
