<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusTrip;
use App\Models\Pilgrim;
use App\Models\BusInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\BusInvoiceRequest;
use App\Http\Resources\Admin\BusInvoiceResource;
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


        if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
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

          if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
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


// public function create(BusInvoiceRequest $request)
// {
//     $this->authorize('manage_system');

//     $busTrip = null;
//     $unavailableSeats = collect();
//     $seatMapArray = [];

//     if ($request->filled('bus_trip_id')) {
//         $busTrip = BusTrip::find($request->bus_trip_id);

//         if (!$busTrip) {
//             return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
//         }

//         $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

//         if ($request->has('pilgrims')) {
//             $requestedSeats = collect($request->pilgrims)->pluck('seatNumber');
//             $availableSeats = collect($seatMapArray)
//                 ->where('status', 'available')
//                 ->pluck('seatNumber');

//             $unavailableSeats = $requestedSeats->diff($availableSeats);

//             if ($unavailableSeats->isNotEmpty()) {
//                 return response()->json([
//                     'message' => 'بعض المقاعد غير متوفرة',
//                     'unavailable_seats' => $unavailableSeats
//                 ], 422);
//             }
//         }
//     }

//     $data = [
//         'seatPrice' => $this->ensureNumeric($request->input('seatPrice')), // إضافة سعر المقعد هنا
//         'discount' => $this->ensureNumeric($request->input('discount')),
//         'tax' => $this->ensureNumeric($request->input('tax')),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
//         'subtotal' => 0,
//         'total' => 0,
//     ];

//     $data = array_merge(
//         $data,
//         $request->except(['discount', 'tax', 'paidAmount', 'pilgrims', 'seatPrice']), // إزالة seatPrice من الـ except
//         $this->prepareCreationMetaData()
//     );

//     DB::beginTransaction();

//     try {
//         $busInvoice = BusInvoice::create($data);

//         if ($request->has('pilgrims')) {
//             $pilgrimsData = [];

//             foreach ($request->pilgrims as $pilgrim) {
//                 if (!isset($pilgrim['id'], $pilgrim['seatNumber'])) { // إزالة التحقق من seatPrice
//                     throw new \Exception('بيانات الحاج غير مكتملة');
//                 }

//                 $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $pilgrim['seatNumber']);

//                 if (!$seatInfo) {
//                     throw new \Exception("المقعد {$pilgrim['seatNumber']} غير موجود في seatMap.");
//                 }

//                 $pilgrimsData[$pilgrim['id']] = [
//                     'seatNumber' => $pilgrim['seatNumber'],
//                     'status' => $pilgrim['status'] ?? 'booked',
//                     'type' => $seatInfo['type'] ?? null,
//                     'position' => $seatInfo['position'] ?? null,
//                     'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
//                     'creationDateHijri' => $this->getHijriDate(),
//                 ];

//                 if ($busTrip) {
//                     $this->updateSeatStatusInTrip($busTrip, $pilgrim['seatNumber'], 'booked');
//                 }
//             }

//             $busInvoice->pilgrims()->attach($pilgrimsData);

//         }

//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal();

//         DB::commit();
//          $busInvoice->load(['pilgrims' => function($query) {
//         $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
//     }]);

//         return $this->respondWithResource($busInvoice, "تم إنشاء فاتورة الباص بنجاح");
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
//     }
// }

// public function create(BusInvoiceRequest $request)
// {
//     $this->authorize('manage_system');

//     $busTrip = null;
//     $unavailableSeats = collect();
//     $seatMapArray = [];

//     if ($request->filled('bus_trip_id')) {
//         $busTrip = BusTrip::find($request->bus_trip_id);

//         if (!$busTrip) {
//             return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
//         }

//         $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

//         if ($request->has('pilgrims')) {
//             $requestedSeats = collect($request->pilgrims)->pluck('seatNumber');
//             $availableSeats = collect($seatMapArray)
//                 ->where('status', 'available')
//                 ->pluck('seatNumber');

//             $unavailableSeats = $requestedSeats->diff($availableSeats);

//             if ($unavailableSeats->isNotEmpty()) {
//                 return response()->json([
//                     'message' => 'بعض المقاعد غير متوفرة',
//                     'unavailable_seats' => $unavailableSeats
//                 ], 422);
//             }
//         }
//     }

//     // جلب سعر المقعد من جدول الباص
//     // $seatPrice = $busTrip->bus->seatPrice ?? 0;

//     $data = [
//         'discount' => $this->ensureNumeric($request->input('discount')),
//         'tax' => $this->ensureNumeric($request->input('tax')),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
//         'subtotal' => 0,
//         'total' => 0,
//     ];

//     $data = array_merge(
//         $data,
//         $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), // حذف seatPrice من هنا
//         $this->prepareCreationMetaData()
//     );

//     DB::beginTransaction();

//     try {
//         $busInvoice = BusInvoice::create($data);

//         if ($request->has('pilgrims')) {
//             $pilgrimsData = [];

//             foreach ($request->pilgrims as $pilgrim) {
//                 if (!isset($pilgrim['id'], $pilgrim['seatNumber'])) {
//                     throw new \Exception('بيانات الحاج غير مكتملة');
//                 }

//                 $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $pilgrim['seatNumber']);

//                 if (!$seatInfo) {
//                     throw new \Exception("المقعد {$pilgrim['seatNumber']} غير موجود في seatMap.");
//                 }

//                 $pilgrimsData[$pilgrim['id']] = [
//                     'seatNumber' => $pilgrim['seatNumber'],
//                     'status' => $pilgrim['status'] ?? 'booked',
//                     'type' => $seatInfo['type'] ?? null,
//                     'position' => $seatInfo['position'] ?? null,
//                     'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
//                     'creationDateHijri' => $this->getHijriDate(),
//                 ];

//                 if ($busTrip) {
//                     $this->updateSeatStatusInTrip($busTrip, $pilgrim['seatNumber'], 'booked');
//                 }
//             }

//             $busInvoice->pilgrims()->attach($pilgrimsData);
//         }

//         // تحديث الحسابات بناءً على عدد المعتمرين وسعر المقعد من جدول الباصات
//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal();

//         DB::commit();
//         $busInvoice->load(['pilgrims' => function($query) {
//             $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
//         }]);

//         return $this->respondWithResource($busInvoice, "تم إنشاء فاتورة الباص بنجاح");
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
//     }
// }


// معتملر يحجز مقعد واحد ب id خاص به
// public function create(BusInvoiceRequest $request)
// {
//     $this->authorize('manage_system');

//     $busTrip = null;
//     $unavailableSeats = collect();
//     $seatMapArray = [];

//     if ($request->filled('bus_trip_id')) {
//         $busTrip = BusTrip::find($request->bus_trip_id);

//         if (!$busTrip) {
//             return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
//         }

//         $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

//         if ($request->has('pilgrims')) {
//             $requestedSeats = collect($request->pilgrims)->pluck('seatNumbers')->flatten();
//             $availableSeats = collect($seatMapArray)
//                 ->where('status', 'available')
//                 ->pluck('seatNumber');

//             $unavailableSeats = $requestedSeats->diff($availableSeats);

//             if ($unavailableSeats->isNotEmpty()) {
//                 return response()->json([
//                     'message' => 'بعض المقاعد غير متوفرة',
//                     'unavailable_seats' => $unavailableSeats
//                 ], 422);
//             }
//         }
//     }

//     // إعداد بيانات الفاتورة
//     $data = [
//         'discount' => $this->ensureNumeric($request->input('discount')),
//         'tax' => $this->ensureNumeric($request->input('tax')),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
//         'subtotal' => 0,
//         'total' => 0,
//     ];

//     $data = array_merge(
//         $data,
//         $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), // حذف `seatPrice`
//         $this->prepareCreationMetaData()
//     );

//     DB::beginTransaction();

//     try {
//         $busInvoice = BusInvoice::create($data);

//         if ($request->has('pilgrims')) {
//             $pilgrimsData = [];

//             foreach ($request->pilgrims as $pilgrim) {
//                 if (!isset($pilgrim['id'], $pilgrim['seatNumbers'])) { // دعم إدخال عدة مقاعد لنفس المعتمر
//                     throw new \Exception('بيانات الحاج غير مكتملة');
//                 }

//                 foreach ($pilgrim['seatNumbers'] as $seatNumber) {
//                     $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

//                     if (!$seatInfo) {
//                         throw new \Exception("المقعد {$seatNumber} غير موجود في seatMap.");
//                     }

//                     $pilgrimsData[] = [
//                         'pilgrim_id' => $pilgrim['id'],
//                         'seatNumber' => $seatNumber,
//                         'status' => $pilgrim['status'] ?? 'booked',
//                         'type' => $seatInfo['type'] ?? null,
//                         'position' => $seatInfo['position'] ?? null,
//                         'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
//                         'creationDateHijri' => $this->getHijriDate(),
//                     ];

//                     if ($busTrip) {
//                         $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
//                     }
//                 }
//             }

//             $busInvoice->pilgrims()->attach($pilgrimsData);
//         }

//         // حساب الإجمالي بناءً على عدد المعتمرين وسعر المقعد من جدول الباصات
//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal();

//         DB::commit();

//         $busInvoice->load(['pilgrims' => function ($query) {
//             $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
//         }]);

//         return $this->respondWithResource($busInvoice, "تم إنشاء فاتورة الباص بنجاح");
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
//     }
// }


// public function create(BusInvoiceRequest $request)
// {
//     $this->authorize('manage_system');

//     $busTrip = null;
//     $unavailableSeats = collect();
//     $seatMapArray = [];

//     if ($request->filled('bus_trip_id')) {
//         $busTrip = BusTrip::find($request->bus_trip_id);

//         if (!$busTrip) {
//             return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
//         }

//         $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

//         if ($request->has('pilgrims')) {
//             $requestedSeats = collect($request->pilgrims)->pluck('seatNumber')->flatten();
//             $availableSeats = collect($seatMapArray)
//                 ->where('status', 'available')
//                 ->pluck('seatNumber');

//             $unavailableSeats = $requestedSeats->diff($availableSeats);

//             if ($unavailableSeats->isNotEmpty()) {
//                 return response()->json([
//                     'message' => 'بعض المقاعد غير متوفرة',
//                     'unavailable_seats' => $unavailableSeats
//                 ], 422);
//             }
//         }
//     }

//     $data = [
//         'discount' => $this->ensureNumeric($request->input('discount')),
//         'tax' => $this->ensureNumeric($request->input('tax')),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
//         'subtotal' => 0,
//         'total' => 0,
//     ];

//     $data = array_merge(
//         $data,
//         $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']),
//         $this->prepareCreationMetaData()
//     );

//     DB::beginTransaction();

//     try {
//         $busInvoice = BusInvoice::create($data);

//         if ($request->has('pilgrims')) {
//             $pilgrimsData = [];

//             foreach ($request->pilgrims as $pilgrim) {
//                 if (!isset($pilgrim['id'], $pilgrim['seatNumber'])) {
//                     throw new \Exception('بيانات الحاج غير مكتملة');
//                 }

//                 foreach ($pilgrim['seatNumber'] as $seatNumber) {
//                     $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

//                     if (!$seatInfo) {
//                         throw new \Exception("المقعد {$seatNumber} غير موجود في seatMap.");
//                     }

//                     $pilgrimsData[] = [
//                         'pilgrim_id' => $pilgrim['id'],
//                         'seatNumber' => $seatNumber,
//                         'status' => $pilgrim['status'] ?? 'booked',
//                         'type' => $seatInfo['type'] ?? null,
//                         'position' => $seatInfo['position'] ?? null,
//                         'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
//                         'creationDateHijri' => $this->getHijriDate(),
//                     ];

//                     if ($busTrip) {
//                         $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
//                     }
//                 }
//             }

//             $busInvoice->pilgrims()->attach($pilgrimsData);
//         }

//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal();

//         DB::commit();

//         $busInvoice->load(['pilgrims' => function ($query) {
//             $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
//         }]);

//         return $this->respondWithResource($busInvoice, "تم إنشاء فاتورة الباص بنجاح");
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
//     }
// }




protected function updateSeatStatusInTrip($busTrip, $seatNumber, $status)
{
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
    $requestedSeats = collect($pilgrims)->pluck('seatNumber');
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
        'pilgrims',
    ])->find($id);

        if (!$busInvoice) {
            return response()->json(['message' => "Bus Invoice not found."], 404);
        }

        return $this->respondWithResource($busInvoice, "Bus Invoice retrieved for editing.");
    }

// public function update(BusInvoiceRequest $request, $id)
// {
//     $this->authorize('manage_system');

//     $busInvoice = BusInvoice::findOrFail($id);
//     $oldData = $busInvoice->toArray();
//     $oldPivot = $busInvoice->pilgrims()
//         ->withPivot(['seatNumber', 'status', 'type', 'position'])
//         ->get()
//         ->keyBy('id')
//         ->map(fn($p) => $p->pivot->toArray())
//         ->toArray();

//     $busTrip = null;
//     $unavailableSeats = collect();
//     $seatMapArray = [];
//     $originalSeats = $busInvoice->pilgrims()->pluck('seatNumber')->toArray();

//     if ($request->filled('bus_trip_id')) {
//         $busTrip = BusTrip::find($request->bus_trip_id);

//         if (!$busTrip) {
//             return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
//         }

//         $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

//         if ($request->has('pilgrims')) {
//             $requestedSeats = collect($request->pilgrims)->pluck('seatNumber');
//             $availableSeats = collect($seatMapArray)->where('status', 'available')->pluck('seatNumber');
//             $availableSeats = $availableSeats->merge($originalSeats)->unique();
//             $unavailableSeats = $requestedSeats->diff($availableSeats);

//             if ($unavailableSeats->isNotEmpty()) {
//                 return response()->json([
//                     'message' => 'بعض المقاعد غير متوفرة',
//                     'unavailable_seats' => $unavailableSeats
//                 ], 422);
//             }
//         }
//     }

//     $data = [
//         'seatPrice' => $this->ensureNumeric($request->input('seatPrice')),
//         'discount' => $this->ensureNumeric($request->input('discount')),
//         'tax' => $this->ensureNumeric($request->input('tax')),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
//         'subtotal' => 0,
//         'total' => 0,
//     ];

//     $data = array_merge(
//         $data,
//         $request->except(['discount', 'tax', 'paidAmount', 'pilgrims', 'seatPrice']),
//         $this->prepareUpdateMetaData()
//     );

//     DB::beginTransaction();

//     try {

//         $hasChanges = $this->checkForChanges($busInvoice, $data, $request);

//         if (!$hasChanges) {
//             $this->loadCommonRelations($busInvoice);
//             return $this->respondWithResource($busInvoice, "لا يوجد تغييرات فعلية");
//         }

//         if ($busTrip && count($originalSeats) > 0) {
//             foreach ($originalSeats as $seat) {
//                 $this->updateSeatStatusInTrip($busTrip, $seat, 'available');
//             }
//         }

//         $busInvoice->update($data);

//         if ($request->has('pilgrims')) {
//             $pilgrimsData = $this->preparePilgrimsData($request->pilgrims, $seatMapArray);
//             $busInvoice->pilgrims()->sync($pilgrimsData);

//             if ($busTrip) {
//                 foreach ($request->pilgrims as $pilgrim) {
//                     $this->updateSeatStatusInTrip($busTrip, $pilgrim['seatNumber'], 'booked');
//                 }
//             }
//         } else {
//             $busInvoice->pilgrims()->detach();
//         }

//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal();

//         $newPivot = $busInvoice->pilgrims()
//             ->withPivot(['seatNumber', 'status', 'type', 'position'])
//             ->get()
//             ->keyBy('id')
//             ->map(fn($p) => $p->pivot->toArray())
//             ->toArray();

//         $pivotChanges = $this->getPivotChanges($oldPivot, $newPivot);

//         $changedData = $busInvoice->getChangedData($oldData, $busInvoice->fresh()->toArray());

//         if (!empty($pivotChanges)) {
//             $changedData['pivot'] = $pivotChanges;
//         }

//         $busInvoice->changed_data = $changedData;
//         $busInvoice->save();

//         DB::commit();

//         $busInvoice->load(['pilgrims' => function($query) {
//             $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
//         }]);

//         return $this->respondWithResource($busInvoice, "تم تحديث فاتورة الباص بنجاح");
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()], 500);
//     }
// }


// معتمر يحجز بال id بتاعه مقعد واحد
// public function update(BusInvoiceRequest $request, $id)
// {
//     $this->authorize('manage_system');

//     $busInvoice = BusInvoice::findOrFail($id);
//     $oldData = $busInvoice->toArray();
//     $oldPivot = $busInvoice->pilgrims()
//         ->withPivot(['seatNumber', 'status', 'type', 'position'])
//         ->get()
//         ->keyBy('id')
//         ->map(fn($p) => $p->pivot->toArray())
//         ->toArray();

//     $busTrip = null;
//     $unavailableSeats = collect();
//     $seatMapArray = [];
//     $originalSeats = $busInvoice->pilgrims()->pluck('seatNumber')->toArray();

//     if ($request->filled('bus_trip_id')) {
//         $busTrip = BusTrip::find($request->bus_trip_id);

//         if (!$busTrip) {
//             return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
//         }

//         $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

//         if ($request->has('pilgrims')) {
//             $requestedSeats = collect($request->pilgrims)->pluck('seatNumber');
//             $availableSeats = collect($seatMapArray)->where('status', 'available')->pluck('seatNumber');
//             $availableSeats = $availableSeats->merge($originalSeats)->unique();
//             $unavailableSeats = $requestedSeats->diff($availableSeats);

//             if ($unavailableSeats->isNotEmpty()) {
//                 return response()->json([
//                     'message' => 'بعض المقاعد غير متوفرة',
//                     'unavailable_seats' => $unavailableSeats
//                 ], 422);
//             }
//         }
//     }

//     $data = [
//         'discount' => $this->ensureNumeric($request->input('discount')),
//         'tax' => $this->ensureNumeric($request->input('tax')),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
//         'subtotal' => 0,
//         'total' => 0,
//     ];

//     $data = array_merge(
//         $data,
//         $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']),
//         $this->prepareUpdateMetaData()
//     );

//     DB::beginTransaction();

//     try {
//         $hasChanges = $this->checkForChanges($busInvoice, $data, $request);

//         if (!$hasChanges) {
//             $this->loadCommonRelations($busInvoice);
//             return $this->respondWithResource($busInvoice, "لا يوجد تغييرات فعلية");
//         }

//         if ($busTrip && count($originalSeats) > 0) {
//             foreach ($originalSeats as $seat) {
//                 $this->updateSeatStatusInTrip($busTrip, $seat, 'available');
//             }
//         }

//         $busInvoice->update($data);

//         if ($request->has('pilgrims')) {
//             $pilgrimsData = $this->preparePilgrimsData($request->pilgrims, $seatMapArray);
//             $busInvoice->pilgrims()->sync($pilgrimsData);

//             if ($busTrip) {
//                 foreach ($request->pilgrims as $pilgrim) {
//                     $this->updateSeatStatusInTrip($busTrip, $pilgrim['seatNumber'], 'booked');
//                 }
//             }
//         } else {
//             $busInvoice->pilgrims()->detach();
//         }

//         $busInvoice->PilgrimsCount();
//         $busInvoice->calculateTotal(); // استدعاء `calculateTotal()` بدون تمرير `seatPrice`

//         $newPivot = $busInvoice->pilgrims()
//             ->withPivot(['seatNumber', 'status', 'type', 'position'])
//             ->get()
//             ->keyBy('id')
//             ->map(fn($p) => $p->pivot->toArray())
//             ->toArray();

//         $pivotChanges = $this->getPivotChanges($oldPivot, $newPivot);

//         $changedData = $busInvoice->getChangedData($oldData, $busInvoice->fresh()->toArray());

//         if (!empty($pivotChanges)) {
//             $changedData['pivot'] = $pivotChanges;
//         }

//         $busInvoice->changed_data = $changedData;
//         $busInvoice->save();

//         DB::commit();

//         $busInvoice->load(['pilgrims' => function ($query) {
//             $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
//         }]);

//         return $this->respondWithResource($busInvoice, "تم تحديث فاتورة الباص بنجاح");
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()], 500);
//     }
// }


public function update(BusInvoiceRequest $request, $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::findOrFail($id);
    $oldData = $busInvoice->toArray();
    $oldPivot = $busInvoice->pilgrims()
        ->withPivot(['seatNumber', 'status', 'type', 'position'])
        ->get()
        ->keyBy('id')
        ->map(fn($p) => $p->pivot->toArray())
        ->toArray();

    $busTrip = null;
    $unavailableSeats = collect();
    $seatMapArray = [];
    $originalSeats = $busInvoice->pilgrims()->pluck('seatNumber')->toArray();

    if ($request->filled('bus_trip_id')) {
        $busTrip = BusTrip::find($request->bus_trip_id);

        if (!$busTrip) {
            return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
        }

        $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);

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
    }

    $data = [
        'discount' => $this->ensureNumeric($request->input('discount')),
        'tax' => $this->ensureNumeric($request->input('tax')),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
        'subtotal' => 0,
        'total' => 0,
    ];

    $data = array_merge(
        $data,
        $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']),
        $this->prepareUpdateMetaData()
    );

    DB::beginTransaction();

    try {
        $hasChanges = $this->checkForChanges($busInvoice, $data, $request);

        if (!$hasChanges) {
            $this->loadCommonRelations($busInvoice);
            return $this->respondWithResource($busInvoice, "لا يوجد تغييرات فعلية");
        }

        if ($busTrip && count($originalSeats) > 0) {
            foreach ($originalSeats as $seat) {
                $this->updateSeatStatusInTrip($busTrip, $seat, 'available');
            }
        }

        $busInvoice->update($data);

        if ($request->has('pilgrims')) {
            $pilgrimsData = $this->preparePilgrimsData($request->pilgrims, $seatMapArray);
            $busInvoice->pilgrims()->sync($pilgrimsData);

            if ($busTrip) {
                foreach ($request->pilgrims as $pilgrim) {
                    $this->updateSeatStatusInTrip($busTrip, $pilgrim['seatNumber'], 'booked');
                }
            }
        } else {
            $busInvoice->pilgrims()->detach();
        }

        $busInvoice->PilgrimsCount();
        $busInvoice->calculateTotal();

        DB::commit();

        return $this->respondWithResource($busInvoice, "تم تحديث فاتورة الباص بنجاح");
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


    // public function updatePaymentStatus(Request $request, $invoiceId)
    // {
    //     $this->authorize('manage_system');

    //     $request->validate([
    //         'paymentStatus' => 'required|in:paid,unpaid,partial',
    //         'paidAmount' => 'required|numeric|min:0',
    //     ]);

    //     $busInvoice = BusInvoice::findOrFail($invoiceId);
    //     $busInvoice->update([
    //         'paymentStatus' => $request->paymentStatus,
    //         'paidAmount' => $request->paidAmount,
    //     ]);

    //     return $this->respondWithResource($busInvoice, "Payment status updated successfully.");
    // }

    // public function getInvoiceStats($invoiceId)
    // {
    //     $this->authorize('manage_system');

    //     $busInvoice = BusInvoice::findOrFail($invoiceId);

    //     return response()->json([
    //         'total_seats' => $busInvoice->pilgrims()->count(),
    //         'booked_seats' => $busInvoice->pilgrims()->wherePivot('status', 'booked')->count(),
    //         'cancelled_seats' => $busInvoice->pilgrims()->wherePivot('status', 'cancelled')->count(),
    //         'subtotal' => $busInvoice->subtotal,
    //         'discount' => $busInvoice->discount,
    //         'tax' => $busInvoice->tax,
    //         'total' => $busInvoice->total,
    //         'paidAmount' => $busInvoice->paidAmount,
    //         'remainingAmount' => $busInvoice->total - $busInvoice->paidAmount,
    //     ]);
    // }


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

    $this->loadCommonRelations($busInvoice);
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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to approved');
}

    public function rejected(string $id)
{
    $this->authorize('manage_system');

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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to rejected');
}

    public function completed(string $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->invoiceStatus === 'completed') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to completed');
    }

    $busInvoice->invoiceStatus = 'completed';
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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to completed');
}

    public function absence(string $id)
{
    $this->authorize('manage_system');

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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to absence');
}

// Payment Status
    public function pendingPayment(string $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->paymentStatus === 'pending') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to pending');
    }

    $busInvoice->paymentStatus = 'pending';
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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice Payment set to pendind');
}

    public function refuneded(string $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->paymentStatus === 'refuneded') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice Payment is already set to refuneded');
    }

    $busInvoice->paymentStatus = 'refuneded';
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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice Payment set to refuneded');
}

    public function paid(string $id)
{
    $this->authorize('manage_system');

    $busInvoice = BusInvoice::find($id);
    if (!$busInvoice) {
        return response()->json(['message' => "BusInvoice not found."], 404);
    }

    $oldData = $busInvoice->toArray();

    if ($busInvoice->paymentStatus === 'paid') {
        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, 'BusInvoice is already set to paid');
    }

    $busInvoice->paymentStatus = 'paid';
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

    $this->loadCommonRelations($busInvoice);
    return $this->respondWithResource($busInvoice, 'BusInvoice set to paid');
}


public function create(BusInvoiceRequest $request) {
    $this->authorize('manage_system');

    $busTrip = null;
    $unavailableSeats = collect();
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
        'discount' => $this->ensureNumeric($request->input('discount')),
        'tax' => $this->ensureNumeric($request->input('tax')),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
        'subtotal' => 0,
        'total' => 0,
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

    DB::beginTransaction();

    try {
        $busInvoice = BusInvoice::create($data);

        if ($request->has('pilgrims')) {
            $pilgrimsData = [];

            foreach ($request->pilgrims as $pilgrim) {
                $existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])
                                          ->orWhere('phoNum', $pilgrim['phoNum'] ?? null)
                                          ->first();

$existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])->first();
if ($existingPilgrim) {
    return response()->json([
        'success' => true,
        'message' => 'المعتمر مسجل مسبقًا، تم إضافته للفاتورة',
        'data' => [
            'pilgrim_id' => $existingPilgrim->id,
            'name' => $existingPilgrim->name
        ]
    ], 200);
}


                $pilgrimRecord = Pilgrim::create([
                    'idNum' => $pilgrim['idNum'] ?? null,
                    'name' => $pilgrim['name'],
                    'phoNum' => $pilgrim['phoNum'] ?? null,
                    'nationality' => $pilgrim['nationality'],
                    'gender' => $pilgrim['gender']
                ]);

                foreach ($pilgrim['seatNumber'] as $seatNumber) {
                    $seatInfo = collect($seatMapArray)->firstWhere('seatNumber', $seatNumber);

                    if (!$seatInfo) {
                        throw new \Exception("المقعد {$seatNumber} غير موجود في seatMap.");
                    }

                    $pilgrimsData[] = [
                        'pilgrim_id' => $pilgrimRecord->id,
                        'seatNumber' => $seatNumber,
                        'status' => $pilgrim['status'] ?? 'booked',
                        'type' => $seatInfo['type'] ?? null,
                        'position' => $seatInfo['position'] ?? null,
                        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                        'creationDateHijri' => $this->getHijriDate(),
                    ];

                    if ($busTrip) {
                        $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                    }
                }
            }

            $busInvoice->pilgrims()->attach($pilgrimsData);
        }

        $busInvoice->PilgrimsCount();
        $busInvoice->calculateTotal();

        DB::commit();

        return $this->respondWithResource($busInvoice->load(['pilgrims' => function ($query) {
            $query->withPivot(['seatNumber', 'status', 'type', 'position', 'creationDate', 'creationDateHijri']);
        }]), "تم إنشاء فاتورة الباص بنجاح");
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
    }
}






        protected function getResourceClass(): string
    {
        return BusInvoiceResource::class;
    }
}
