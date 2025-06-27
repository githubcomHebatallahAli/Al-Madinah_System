<?php

namespace App\Http\Controllers\Admin;


use App\Models\Hotel;
use App\Models\Pilgrim;
use App\Models\BusInvoice;
use App\Models\HotelInvoice;
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
use App\Http\Requests\Admin\HotelInvoiceRequest;
use App\Http\Resources\Admin\HotelInvoiceResource;
use App\Http\Resources\Admin\ShowAllHotelInvoiceResource;


class HotelInvoiceController extends Controller
{
     use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;


    protected function findOrCreatePilgrimForInvoice(array $pilgrimData): Pilgrim
{
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

protected function attachPilgrims(HotelInvoice $invoice, array $pilgrims)
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

protected function syncPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);
        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $p->id)->first();

        $pilgrimsData[$p->id] = [
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

    $newPilgrimsIds = [];
    foreach ($newPilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);
        $newPilgrimsIds[] = $p->id;
    }

    sort($currentPilgrims);
    sort($newPilgrimsIds);

    return $currentPilgrims !== $newPilgrimsIds;
}



        public function showAllWithPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = HotelInvoice::query();

             if ($request->filled('bus_invoice_id')) {
            $query->where('bus_invoice_id', $request->bus_invoice_id);
        }


        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }



        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $hotelInvoices = $query->with(['hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = HotelInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllHotelInvoiceResource::collection($hotelInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'pagination' => [
                'total' => $hotelInvoices->total(),
                'count' => $hotelInvoices->count(),
                'per_page' => $hotelInvoices->perPage(),
                'current_page' => $hotelInvoices->currentPage(),
                'total_pages' => $hotelInvoices->lastPage(),
                'next_page_url' => $hotelInvoices->nextPageUrl(),
                'prev_page_url' => $hotelInvoices->previousPageUrl(),
            ],
            'message' => "Show All Hotel Invoices."
        ]);
    }

    public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = HotelInvoice::query();

        if ($request->filled('bus_invoice_id')) {
            $query->where('bus_invoice_id', $request->bus_invoice_id);
        }


        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }


        $hotelInvoices = $query->with(['hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = HotelInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllHotelInvoiceResource::collection($hotelInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,

        ],
            'message' => "Show All Hotel Invoices."
        ]);
    }

// public function create(HotelInvoiceRequest $request)
// {
//     $this->authorize('manage_system');

//     $data = array_merge([
//         'discount' => $this->ensureNumeric($request->input('discount', 0)),
//         'tax' => $this->ensureNumeric($request->input('tax', 0)),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
//         'subtotal' => 0,
//         'total' => 0,
//     ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

//     DB::beginTransaction();
//     try {
//         $invoice = HotelInvoice::create($data);


//         if ($request->has('pilgrims')) {
//             $this->attachPilgrims($invoice, $request->pilgrims);
//         }

//         if ($request->filled('bus_invoice_id')) {
//             $this->attachBusPilgrims($invoice, $request->bus_invoice_id);
//         }

//         $invoice->PilgrimsCount();
//         $invoice->calculateTotal();
//         DB::commit();

//         return $this->respondWithResource(
//             new HotelInvoiceResource($invoice->load([ 'paymentMethodType.paymentMethod',
//             'mainPilgrim',
//             'hotel', 'trip', 'busInvoice','pilgrims'])),
//             'تم إنشاء فاتورة الفندق بنجاح'
//         );

//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json([
//             'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()
//         ], 500);
//     }
// }

// public function create(HotelInvoiceRequest $request)
// {
//     $this->authorize('manage_system');

//     // التحقق من توفر الغرفة أولاً
//     if ($request->has('roomNum')) {
//         $hotel = Hotel::find($request->hotel_id);
//         if (!$hotel->isRoomAvailable($request->roomNum)) {
//             return response()->json([
//                 'message' => 'الغرفة غير متاحة للحجز'
//             ], 400);
//         }
//     }

//     $data = array_merge([
//         'discount' => $this->ensureNumeric($request->input('discount', 0)),
//         'tax' => $this->ensureNumeric($request->input('tax', 0)),
//         'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
//         'subtotal' => 0,
//         'total' => 0,
//     ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']),
//     $this->prepareCreationMetaData());

//     DB::beginTransaction();
//     try {
//         // إنشاء الفاتورة
//         $invoice = HotelInvoice::create($data);


//         if ($request->has('pilgrims')) {
//             $this->attachPilgrims($invoice, $request->pilgrims);
//         }


//         if ($request->filled('bus_invoice_id')) {
//             $this->attachBusPilgrims($invoice, $request->bus_invoice_id);
//         }


//         $invoice->PilgrimsCount();
//         $invoice->calculateTotal();

//         DB::commit();

//         return $this->respondWithResource(
//             new HotelInvoiceResource($invoice->load([
//                 'paymentMethodType.paymentMethod',
//                 'mainPilgrim',
//                 'hotel',
//                 'trip',
//                 'busInvoice',
//                 'pilgrims'
//             ])),
//             'تم إنشاء فاتورة الفندق بنجاح'
//         );

//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json([
//             'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()
//         ], 500);
//     }
// }


public function create(HotelInvoiceRequest $request)
{
    $this->authorize('manage_system');

    // التحقق من توفر الغرفة أولاً
    if ($request->has('roomNum')) {
        $hotel = Hotel::find($request->hotel_id);
        if (!$hotel->isRoomAvailable($request->roomNum)) {
            return response()->json([
                'message' => 'الغرفة غير متاحة للحجز'
            ], 400);
        }
    }

    // تحويل التواريخ إلى هجري
    $hijriDates = [];
    if ($request->has('checkInDate')) {
        $hijriDates['checkInDateHijri'] = $this->getHijriDate($request->checkInDate);
    }
    if ($request->has('checkOutDate')) {
        $hijriDates['checkOutDateHijri'] = $this->getHijriDate($request->checkOutDate);
    }

    $data = array_merge([
        'discount' => $this->ensureNumeric($request->input('discount', 0)),
        'tax' => $this->ensureNumeric($request->input('tax', 0)),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
        'subtotal' => 0,
        'total' => 0,
        'creationDateHijri' => $this->getHijriDate(now()), // تاريخ الإنشاء الهجري
    ],
    $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']),
    $hijriDates,
    $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        // إنشاء الفاتورة
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
            new HotelInvoiceResource($invoice->load([
                'paymentMethodType.paymentMethod',
                'mainPilgrim',
                'hotel',
                'trip',
                'busInvoice',
                'pilgrims'
            ])),
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


        public function edit(string $id)
    {
        $this->authorize('manage_system');

        $hotelInvoice =HotelInvoice::with([
          'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims'
    ])->find($id);

        if (!$hotelInvoice) {
            return response()->json(['message' => "Hotel Invoice not found."], 404);
        }

        return $this->respondWithResource($hotelInvoice, "Hotel Invoice retrieved for editing.");
    }

public function update(HotelInvoiceRequest $request, HotelInvoice $hotelInvoice)
{
    $this->authorize('manage_system');


    if (in_array($hotelInvoice->invoiceStatus, ['approved', 'completed'])) {
        return response()->json([
            'message' => 'لا يمكن تعديل فاتورة معتمدة أو مكتملة'
        ], 422);
    }


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

        $data = array_merge([
            'discount' => $this->ensureNumeric($request->input('discount')),
            'tax' => $this->ensureNumeric($request->input('tax')),
            'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
        ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareUpdateMetaData());

        $hasChanges = false;
        foreach ($data as $key => $value) {
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

        $hotelInvoice->update($data);


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


 // Invoice Status Methods
public function pending($id)
{
    $this->authorize('manage_system');

    $hotelInvoice = HotelInvoice::find($id);
    if (!$hotelInvoice) {
        return response()->json(['message' => "Hotel Invoice not found."], 404);
    }

    $oldData = $hotelInvoice->toArray();

    if ($hotelInvoice->invoiceStatus === 'pending') {
        $hotelInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims']);
        return $this->respondWithResource($hotelInvoice, 'Hotel Invoice is already set to pending');
    }

    $hotelInvoice->invoiceStatus = 'pending';
    $hotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hotelInvoice->creationDateHijri = $this->getHijriDate();
    $hotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $hotelInvoice->updated_by_type = $this->getUpdatedByType();
    $hotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $hotelInvoice->creationDate,
        'creationDateHijri' => $hotelInvoice->creationDateHijri,
    ];

    $changedData = $hotelInvoice->getChangedData($oldData, array_merge($hotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $hotelInvoice->changed_data = $changedData;
    $hotelInvoice->save();

    $hotelInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims']);
    return $this->respondWithResource($hotelInvoice, 'Hotel Invoice set to pending');
}

public function approved($id)
{
    $this->authorize('manage_system');

    $hotelInvoice = HotelInvoice::find($id);
    if (!$hotelInvoice) {
        return response()->json(['message' => "Hotel Invoice not found."], 404);
    }

    $oldData = $hotelInvoice->toArray();

    if ($hotelInvoice->invoiceStatus === 'approved') {
        $hotelInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims']);
        return $this->respondWithResource($hotelInvoice, 'Hotel Invoice is already set to approved');
    }

    $hotelInvoice->invoiceStatus = 'approved';
    $hotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hotelInvoice->creationDateHijri = $this->getHijriDate();
    $hotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $hotelInvoice->updated_by_type = $this->getUpdatedByType();
    $hotelInvoice->save();

    //  $hotelInvoice->PilgrimsCount();
    // $hotelInvoice->calculateTotal();

    $metaForDiffOnly = [
        'creationDate' => $hotelInvoice->creationDate,
        'creationDateHijri' => $hotelInvoice->creationDateHijri,
    ];

    $changedData = $hotelInvoice->getChangedData($oldData, array_merge($hotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $hotelInvoice->changed_data = $changedData;
    $hotelInvoice->save();

    $hotelInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims']);
    return $this->respondWithResource($hotelInvoice, 'Hotel Invoice set to approved');
}

public function rejected(string $id, Request $request)
{
    $this->authorize('manage_system');
     $validated = $request->validate([
        'reason' => 'nullable|string',
    ]);

    $hotelInvoice = HotelInvoice::find($id);
    if (!$hotelInvoice) {
        return response()->json(['message' => "Hotel Invoice not found."], 404);
    }

    $oldData = $hotelInvoice->toArray();

    if ($hotelInvoice->invoiceStatus === 'rejected') {
        $hotelInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims']);
        return $this->respondWithResource($hotelInvoice, 'Hotel Invoice is already set to rejected');
    }

    $hotelInvoice->invoiceStatus = 'rejected';
    $hotelInvoice->reason = $validated['reason'] ?? null;
    // $hotelInvoice->subtotal = 0;
    // $hotelInvoice->total = 0;
    // $hotelInvoice->paidAmount = 0;
    $hotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hotelInvoice->creationDateHijri = $this->getHijriDate();
    $hotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $hotelInvoice->updated_by_type = $this->getUpdatedByType();
    $hotelInvoice->save();

    //  $hotelInvoice->PilgrimsCount();
    // $hotelInvoice->calculateTotal();

    $metaForDiffOnly = [
        'creationDate' => $hotelInvoice->creationDate,
        'creationDateHijri' => $hotelInvoice->creationDateHijri,
    ];

    $changedData = $hotelInvoice->getChangedData($oldData, array_merge($hotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $hotelInvoice->changed_data = $changedData;
    $hotelInvoice->save();

    $hotelInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims']);
    return $this->respondWithResource($hotelInvoice, 'Hotel Invoice set to rejected');
}


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
        $hotelInvoice = HotelInvoice::with([
            'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'hotel', 'trip', 'busInvoice','pilgrims'
        ])->findOrFail($id);

        if ($hotelInvoice->invoiceStatus === 'completed') {
            $this->loadCommonRelations($hotelInvoice);
            DB::commit();
            return $this->respondWithResource($hotelInvoice, 'فاتورة الفندق مكتملة مسبقاً');
        }

        $originalData = $hotelInvoice->getOriginal();

        // تحديث الحقول الرئيسية
        $hotelInvoice->invoiceStatus = 'completed';
        $hotelInvoice->payment_method_type_id = $validated['payment_method_type_id'];
        $hotelInvoice->paidAmount = $validated['paidAmount'];
        $hotelInvoice->discount = $validated['discount'] ?? 0;
        $hotelInvoice->tax = $validated['tax'] ?? 0;
        $hotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
        $hotelInvoice->updated_by_type = $this->getUpdatedByType();

        // تسجيل التغييرات الأساسية
        $changedData = [];
        foreach ($hotelInvoice->getDirty() as $field => $newValue) {
            if (array_key_exists($field, $originalData)) {
                $changedData[$field] = [
                    'old' => $originalData[$field],
                    'new' => $newValue
                ];
            }
        }

        // معالجة خاصة لطريقة الدفع
        if ($hotelInvoice->isDirty('payment_method_type_id')) {
            $paymentMethodType = PaymentMethodType::with('paymentMethod')
                ->find($validated['payment_method_type_id']);

            $changedData['payment_method'] = [
                'old' => [
                    'type' => $hotelInvoice->paymentMethodType?->type,
                    'by' => $hotelInvoice->paymentMethodType?->by,
                    'method' => $hotelInvoice->paymentMethodType?->paymentMethod?->name
                ],
                'new' => $paymentMethodType ? [
                    'type' => $paymentMethodType->type,
                    'by' => $paymentMethodType->by,
                    'method' => $paymentMethodType->paymentMethod?->name
                ] : null
            ];
        }

        if (!empty($changedData)) {
            $previousChanged = $hotelInvoice->changed_data ?? [];

            $newCreationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
            $newCreationDateHijri = $this->getHijriDate();

            $changedData['creationDate'] = [
                'old' => $previousChanged['creationDate']['new'] ?? $hotelInvoice->getOriginal('creationDate'),
                'new' => $newCreationDate
            ];

            $changedData['creationDateHijri'] = [
                'old' => $previousChanged['creationDateHijri']['new'] ?? $hotelInvoice->getOriginal('creationDateHijri'),
                'new' => $newCreationDateHijri
            ];

        }

        $hotelInvoice->PilgrimsCount();
        $hotelInvoice->calculateTotal();

        $hotelInvoice->changed_data = $changedData;
        $hotelInvoice->save();

        $this->loadCommonRelations($hotelInvoice);
        DB::commit();

        return $this->respondWithResource(
            $hotelInvoice,
            'تم إكمال فاتورة الفندق بنجاح'
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



public function absence(string $id, Request $request)
{
    $this->authorize('manage_system');

    $validated = $request->validate([
        'reason' => 'nullable|string',
    ]);

    $hotelInvoice = HotelInvoice::find($id);
    if (!$hotelInvoice) {
        return response()->json(['message' => "Hotel Invoice not found."], 404);
    }

    $oldData = $hotelInvoice->toArray();

    if ($hotelInvoice->invoiceStatus === 'absence') {
        $this->loadCommonRelations($hotelInvoice);
        return $this->respondWithResource($hotelInvoice, 'Hotel Invoice is already set to absence');
    }

    $hotelInvoice->invoiceStatus = 'absence';
    $hotelInvoice->reason = $validated['reason'] ?? null;
    $hotelInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hotelInvoice->creationDateHijri = $this->getHijriDate();
    $hotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $hotelInvoice->updated_by_type = $this->getUpdatedByType();
    $hotelInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $hotelInvoice->creationDate,
        'creationDateHijri' => $hotelInvoice->creationDateHijri,
    ];

    $changedData = $hotelInvoice->getChangedData($oldData, array_merge($hotelInvoice->fresh()->toArray(), $metaForDiffOnly));
    $hotelInvoice->changed_data = $changedData;
    $hotelInvoice->save();


    return $this->respondWithResource($hotelInvoice, 'Hotel Invoice set to absence');
}


        protected function getResourceClass(): string
    {
        return HotelInvoiceResource::class;
    }


protected function attachBusPilgrims(HotelInvoice $invoice, $hotelInvoiceId)
{
    if (empty($hotelInvoiceId)) {
        return;
    }

    $hotelInvoice = BusInvoice::with('pilgrims')->find($hotelInvoiceId);

    if (!$hotelInvoice) {
        throw new \Exception('عفواً، فاتورة الباص المحددة غير موجودة!');
    }

    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $pilgrimsData = $hotelInvoice->pilgrims->mapWithKeys(function ($pilgrim) use ($currentDate, $hijriDate) {
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



protected function preparePilgrimsData(array $pilgrims): array
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        // للأطفال بدون idNum
        if (empty($pilgrim['idNum'])) {
            $p = Pilgrim::create([
                'name' => $pilgrim['name'],
                'nationality' => $pilgrim['nationality'],
                'gender' => $pilgrim['gender'],
                'phoNum' => null,
                'idNum' => null
            ]);
        } else {
            $p = Pilgrim::where('idNum', $pilgrim['idNum'])->firstOrFail();
        }

        $pilgrimsData[$p->id] = [
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    return $pilgrimsData;
}

}
