<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusTrip;
use App\Models\Pilgrim;
use App\Models\BusInvoice;
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
use App\Http\Requests\Admin\BusInvoiceRequest;
use App\Http\Resources\Admin\BusInvoiceResource;
use App\Http\Requests\Admin\UpdatePilgrimDataRequest;
use App\Http\Resources\Admin\ShowAllBusInvoiceResource;


class BusInvoiceController extends Controller
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

        $query = BusInvoice::query();

        if ($request->filled('bus_trip_id')) {
            $query->where('bus_trip_id', $request->bus_trip_id);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }


        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $busInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = BusInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllBusInvoiceResource::collection($busInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'pagination' => [
                'total' => $busInvoices->total(),
                'count' => $busInvoices->count(),
                'per_page' => $busInvoices->perPage(),
                'current_page' => $busInvoices->currentPage(),
                'total_pages' => $busInvoices->lastPage(),
                'next_page_url' => $busInvoices->nextPageUrl(),
                'prev_page_url' => $busInvoices->previousPageUrl(),
            ],
            'message' => "Show All Bus Invoices."
        ]);
    }

    public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = BusInvoice::query();

        if ($request->filled('bus_trip_id')) {
            $query->where('bus_trip_id', $request->bus_trip_id);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }


        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }


        $busInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = BusInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllBusInvoiceResource::collection($busInvoices),
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

        $busInvoice =BusInvoice::with([
        'pilgrims','mainPilgrim'
    ])->find($id);

        if (!$busInvoice) {
            return response()->json(['message' => "Bus Invoice not found."], 404);
        }

        return $this->respondWithResource($busInvoice, "Bus Invoice retrieved for editing.");
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

protected function checkForChanges($busInvoice, $newData, $request): bool
{
    foreach ($newData as $key => $value) {
        if ($busInvoice->$key != $value) {
            return true;
        }
    }

    if ($request->has('pilgrims')) {
        $currentPilgrims = $busInvoice->pilgrims()->pluck('pilgrims.id')->toArray();
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

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->invoiceStatus === 'pending') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to pending');
    }

    $busInvoice->invoiceStatus = 'pending';
    $busInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $busInvoice->creationDateHijri = $this->getHijriDate();
    $busInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $busInvoice->updated_by_type = $this->getUpdatedByType();
    $busInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $busInvoice->creationDate,
        'creationDateHijri' => $busInvoice->creationDateHijri,
    ];

    $changedData = $busInvoice->getChangedData($oldData, array_merge($busInvoice->fresh()->toArray(), $metaForDiffOnly));
    $busInvoice->changed_data = $changedData;
    $busInvoice->save();

    // $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to pending');
}

    public function approved(string $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->invoiceStatus === 'approved') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to approved');
    }

    $busInvoice->invoiceStatus = 'approved';
    $busInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $busInvoice->creationDateHijri = $this->getHijriDate();
    $busInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $busInvoice->updated_by_type = $this->getUpdatedByType();
    $busInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $busInvoice->creationDate,
        'creationDateHijri' => $busInvoice->creationDateHijri,
    ];

    $changedData = $busInvoice->getChangedData($oldData, array_merge($busInvoice->fresh()->toArray(), $metaForDiffOnly));
    $busInvoice->changed_data = $changedData;
    $busInvoice->save();


    return $this->respondWithResource($busInvoice, 'BusInvoice set to approved');
}

    public function rejected($id, Request $request)
{
    $this->authorize('manage_system');

    $validated = $request->validate([
        'reason' => 'nullable|string',
    ]);

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->invoiceStatus === 'rejected') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to rejected');
    }

    $busInvoice->invoiceStatus = 'rejected';
    $busInvoice->reason = $validated['reason'] ?? null;
    $busInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $busInvoice->creationDateHijri = $this->getHijriDate();
    $busInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $busInvoice->updated_by_type = $this->getUpdatedByType();
    $busInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $busInvoice->creationDate,
        'creationDateHijri' => $busInvoice->creationDateHijri,
    ];

    $changedData = $busInvoice->getChangedData($oldData, array_merge($busInvoice->fresh()->toArray(), $metaForDiffOnly));
    $busInvoice->changed_data = $changedData;
    $busInvoice->save();

    // $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to rejected');
}




    public function absence($id, Request $request)
{

       $this->authorize('manage_system');
    $validated = $request->validate([
        'reason' => 'nullable|string',
    ]);

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->invoiceStatus === 'absence') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to absence');
    }

    $busInvoice->invoiceStatus = 'absence';
     $busInvoice->reason = $validated['reason'] ?? null;
    $busInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $busInvoice->creationDateHijri = $this->getHijriDate();
    $busInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $busInvoice->updated_by_type = $this->getUpdatedByType();
    $busInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $busInvoice->creationDate,
        'creationDateHijri' => $busInvoice->creationDateHijri,
    ];

    $changedData = $busInvoice->getChangedData($oldData, array_merge($busInvoice->fresh()->toArray(), $metaForDiffOnly));
    $busInvoice->changed_data = $changedData;
    $busInvoice->save();

    // $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to absence');
}

// public function completed($id, Request $request)
// {
//     $this->authorize('manage_system');

//     // التحقق من الصلاحيات والبيانات
//     $validated = $request->validate([
//         'payment_method_type_id' => 'required|exists:payment_method_types,id',
//         'paidAmount' => 'required|numeric|min:0|max:99999.99',
//         'discount' => 'nullable|numeric|min:0|max:99999.99',
//         'tax' => 'nullable|numeric|min:0|max:99999.99'
//     ]);

//     DB::beginTransaction();

//     try {
//         $busInvoice = BusInvoice::with(['paymentMethodType.paymentMethod'])->findOrFail($id);

//         // الشرط الجديد: التحقق من أن paidAmount لا يتجاوز total
//         if (floatval($validated['paidAmount']) > floatval($busInvoice->total)) {
//             return response()->json([
//                 'message' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة',
//                 'total_amount' => $busInvoice->total,
//                 'paid_amount' => $validated['paidAmount']
//             ], 422);
//         }

//         if ($busInvoice->invoiceStatus === 'completed') {
//             $this->loadCommonRelations($busInvoice);
//             DB::commit();
//             return $this->respondWithResource($busInvoice, 'فاتورة الحافلة مكتملة مسبقاً');
//         }

//         // حفظ البيانات الأصلية
//         $originalData = $busInvoice->getOriginal();

//         // تحضير بيانات التحديث (تم تصحيح كتابة validated)
//         $updateData = [
//             'invoiceStatus' => 'completed',
//             'payment_method_type_id' => $validated['payment_method_type_id'],
//             'paidAmount' => $validated['paidAmount'],
//             'discount' => $validated['discount'] ?? 0, // تصحيح typo من discount إلى discount
//             'tax' => $validated['tax'] ?? 0,
//             'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
//             'creationDateHijri' => $this->getHijriDate(),
//             'updated_by' => $this->getUpdatedByIdOrFail(),
//             'updated_by_type' => $this->getUpdatedByType()
//         ];

//         // حساب التغييرات
//         $changedData = [];
//         foreach ($updateData as $field => $newValue) {
//             if (array_key_exists($field, $originalData)) {
//                 $oldValue = $originalData[$field];

//                 if ($oldValue != $newValue) {
//                     $changedData[$field] = [
//                         'old' => $oldValue,
//                         'new' => $newValue
//                     ];
//                 }
//             }
//         }

//         // تتبع تغيير طريقة الدفع
//         if ($busInvoice->payment_method_type_id != $validated['payment_method_type_id']) {
//             $paymentMethodType = PaymentMethodType::with('paymentMethod')
//                 ->find($validated['payment_method_type_id']);

//             $changedData['payment_method'] = [
//                 'old' => [
//                     'type' => $busInvoice->paymentMethodType?->type,
//                     'by' => $busInvoice->paymentMethodType?->by,
//                     'method' => $busInvoice->paymentMethodType?->paymentMethod?->name
//                 ],
//                 'new' => $paymentMethodType ? [
//                     'type' => $paymentMethodType->type,
//                     'by' => $paymentMethodType->by,
//                     'method' => $paymentMethodType->paymentMethod?->name
//                 ] : null
//             ];
//         }

//         // تطبيق التغييرات
//         $busInvoice->fill($updateData);
//         $busInvoice->changed_data = $changedData;
//         $busInvoice->save();

//         // تحديث الحسابات
//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal();

//         $this->loadCommonRelations($busInvoice);
//         DB::commit();

//         return $this->respondWithResource($busInvoice, 'تم إكمال فاتورة الحافلة بنجاح');

//     } catch (\Exception $e) {
//         DB::rollBack();

//         Log::error('فشل إكمال الفاتورة: ' . $e->getMessage(), [
//             'invoice_id' => $id,
//             'error' => $e->getTraceAsString()
//         ]);

//         return response()->json([
//             'message' => 'فشل في إكمال الفاتورة: ' . $e->getMessage()
//         ], 500);
//     }
// }


public function completed($id, Request $request)
{
    $this->authorize('manage_system');

    $validated = $request->validate([
        'payment_method_type_id' => 'required|exists:payment_method_types,id',
        'paidAmount' => 'required|numeric|min:0|max:99999.99',
        'discount' => 'nullable|numeric|min:0|max:99999.99',
        'tax' => 'nullable|numeric|min:0|max:99999.99'
    ]);

    DB::beginTransaction();

    try {
        // تحميل العلاقات الأساسية مسبقاً
        $busInvoice = BusInvoice::with([
            'paymentMethodType.paymentMethod',
            'mainPilgrim'
        ])->findOrFail($id);

        if (floatval($validated['paidAmount']) > floatval($busInvoice->total)) {
            return response()->json([
                'message' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة',
                'total_amount' => $busInvoice->total,
                'paid_amount' => $validated['paidAmount']
            ], 422);
        }

        if ($busInvoice->invoiceStatus === 'completed') {
            $this->loadCommonRelations($busInvoice);
            DB::commit();
            return $this->respondWithResource(
                $busInvoice->load(['pilgrims', 'busTrip', 'campaign', 'office', 'group', 'worker']),
                'فاتورة الحافلة مكتملة مسبقاً'
            );
        }

        $originalData = $busInvoice->getOriginal();

        $updateData = [
            'invoiceStatus' => 'completed',
            'payment_method_type_id' => $validated['payment_method_type_id'],
            'paidAmount' => $validated['paidAmount'],
            'discount' => $validated['discount'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            'creationDateHijri' => $this->getHijriDate(),
            'updated_by' => $this->getUpdatedByIdOrFail(),
            'updated_by_type' => $this->getUpdatedByType()
        ];

        $changedData = [];
        foreach ($updateData as $field => $newValue) {
            if (array_key_exists($field, $originalData)) {
                $oldValue = $originalData[$field];
                if ($oldValue != $newValue) {
                    $changedData[$field] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
        }

        if ($busInvoice->payment_method_type_id != $validated['payment_method_type_id']) {
            $paymentMethodType = PaymentMethodType::with('paymentMethod')
                ->find($validated['payment_method_type_id']);

            $changedData['payment_method'] = [
                'old' => [
                    'type' => $busInvoice->paymentMethodType?->type,
                    'by' => $busInvoice->paymentMethodType?->by,
                    'method' => $busInvoice->paymentMethodType?->paymentMethod?->name
                ],
                'new' => $paymentMethodType ? [
                    'type' => $paymentMethodType->type,
                    'by' => $paymentMethodType->by,
                    'method' => $paymentMethodType->paymentMethod?->name
                ] : null
            ];
        }

        $busInvoice->fill($updateData);
        $busInvoice->changed_data = $changedData;
        $busInvoice->save();

        $busInvoice->PilgrimsCount();
        $busInvoice->calculateTotal();

        // تحميل جميع العلاقات المطلوبة قبل الإرجاع
        $busInvoice->load([
            'pilgrims',
            'busTrip',
            'campaign',
            'office',
            'group',
            'worker',
            'paymentMethodType.paymentMethod',
            'mainPilgrim'
        ]);

        DB::commit();

        return $this->respondWithResource(
            $busInvoice,
            'تم إكمال فاتورة الحافلة بنجاح'
        );

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('فشل إكمال الفاتورة: ' . $e->getMessage(), [
            'invoice_id' => $id,
            'error' => $e->getTraceAsString()
        ]);
        return response()->json([
            'message' => 'فشل في إكمال الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}



protected function attachPilgrims(BusInvoice $invoice, array $pilgrims, array $seatMapArray = [], ?BusTrip $busTrip = null): void
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        $existingPilgrim = $this->findOrCreatePilgrim($pilgrim);

        foreach ($pilgrim['seatNumber'] as $seatNumber) {
            $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

            if (!$seatInfo) {
                throw new \Exception("المقعد {$seatNumber} غير موجود");
            }

            $pilgrimsData[$existingPilgrim->id] = [
                'seatNumber' => $seatNumber,
                'status' => 'booked',
                'type' => $seatInfo['type'] ?? null,
                'position' => $seatInfo['position'] ?? null,
                'creationDate' => $currentDate,
                'creationDateHijri' => $hijriDate,
            ];

            if ($busTrip) {
                $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
            }
        }
    }

    $invoice->pilgrims()->sync($pilgrimsData);
}


protected function findOrCreatePilgrim(array $pilgrimData): Pilgrim
{
    // الحالة 1: عندما لا يوجد رقم هوية (الأطفال)
    if (empty($pilgrimData['idNum'])) {
        if (!isset($pilgrimData['name'], $pilgrimData['nationality'], $pilgrimData['gender'])) {
            throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع على الأقل');
        }

        $existingChild = Pilgrim::whereNull('idNum')
            ->where('name', $pilgrimData['name'])
            ->where('nationality', $pilgrimData['nationality'])
            ->where('gender', $pilgrimData['gender'])
            ->first();

        return $existingChild ?? Pilgrim::create([
            'name' => $pilgrimData['name'],
            'nationality' => $pilgrimData['nationality'],
            'gender' => $pilgrimData['gender'],
            'phoNum' => $pilgrimData['phoNum'] ?? null,
            'idNum' => null
        ]);
    }

    // الحالة 2: عندما يوجد رقم هوية
    $pilgrim = Pilgrim::where('idNum', $pilgrimData['idNum'])->first();

    if (!$pilgrim) {
        if (!isset($pilgrimData['name'], $pilgrimData['nationality'], $pilgrimData['gender'])) {
            throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع على الأقل');
        }

        return Pilgrim::create([
            'idNum' => $pilgrimData['idNum'],
            'name' => $pilgrimData['name'],
            'nationality' => $pilgrimData['nationality'],
            'gender' => $pilgrimData['gender'],
            'phoNum' => $pilgrimData['phoNum'] ?? null
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


        protected function getResourceClass(): string
    {
        return BusInvoiceResource::class;
    }


    public function create(BusInvoiceRequest $request)
{
    $this->authorize('manage_system');

    $busTrip = null;
    $seatMapArray = [];


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
        'discount' => $this->ensureNumeric($request->input('discount', 0)),
        'tax' => $this->ensureNumeric($request->input('tax', 0)),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
        'subtotal' => 0,
        'total' => 0,
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        $busInvoice = BusInvoice::create($data);

        if ($request->has('pilgrims')) {
            $this->attachPilgrims($busInvoice, $request->pilgrims, $seatMapArray, $busTrip);
        }

        $busInvoice->PilgrimsCount();
        $busInvoice->calculateTotal();

        DB::commit();

        return response()->json([
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'invoice' => new BusInvoiceResource($busInvoice->load([
                'pilgrims', 'busTrip', 'campaign', 'office', 'group', 'worker', 'paymentMethodType','mainPilgrim'
            ])),
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}

public function update(BusInvoiceRequest $request, $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::with(['pilgrims', 'busTrip'])->findOrFail($id);
    $busTrip = $busInvoice->busTrip;
    $seatMapArray = $busTrip ? json_decode(json_encode($busTrip->seatMap), true) : [];
    $originalSeats = $busInvoice->pilgrims->pluck('pivot.seatNumber')->toArray();

    if (in_array($busInvoice->invoiceStatus, ['approved', 'completed'])) {
        return response()->json([
            'message' => 'لا يمكن تعديل فاتورة معتمدة أو مكتملة'
        ], 422);
    }


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
        'discount' => $this->ensureNumeric($request->input('discount', 0)),
        'tax' => $this->ensureNumeric($request->input('tax', 0)),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareUpdateMetaData());

    DB::beginTransaction();
    try {

        $busInvoice->update($data);


        if ($request->has('pilgrims')) {
            $this->attachPilgrims($busInvoice, $request->pilgrims, $seatMapArray, $busTrip);
        }


        $busInvoice->PilgrimsCount();
        $busInvoice->calculateTotal();

        DB::commit();

        return response()->json([
            'data' => new BusInvoiceResource($busInvoice->load([
                'pilgrims', 'busTrip', 'campaign', 'office', 'group', 'worker', 'paymentMethodType','mainPilgrim'
            ])),
            'message' => 'تم تحديث الفاتورة بنجاح'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}
}
