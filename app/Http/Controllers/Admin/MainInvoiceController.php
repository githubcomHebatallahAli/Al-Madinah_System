<?php

namespace App\Http\Controllers\Admin;

use App\Models\Hotel;
use App\Models\BusTrip;
use App\Models\Pilgrim;
use App\Models\IhramSupply;
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
use App\Http\Requests\Admin\MainInvoiceRequest;
use App\Traits\HandlesInvoiceStatusChangeTrait;
use App\Http\Resources\Admin\MainInvoiceResource;
use App\Http\Resources\Admin\ShowAllMainInvoiceResource;

class MainInvoiceController extends Controller
{
    use HandlesInvoiceStatusChangeTrait;
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

        public function showAllWithPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = MainInvoice::query();

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

        $MainInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = MainInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllMainInvoiceResource::collection($MainInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'pagination' => [
                'total' => $MainInvoices->total(),
                'count' => $MainInvoices->count(),
                'per_page' => $MainInvoices->perPage(),
                'current_page' => $MainInvoices->currentPage(),
                'total_pages' => $MainInvoices->lastPage(),
                'next_page_url' => $MainInvoices->nextPageUrl(),
                'prev_page_url' => $MainInvoices->previousPageUrl(),
            ],
            'message' => "Show All Bus Invoices."
        ]);
    }

    public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = MainInvoice::query();

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

        $MainInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = MainInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllMainInvoiceResource::collection($MainInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,

        ],
            'message' => "Show All Bus Invoices."
        ]);
    }

        protected function ensureNumeric($value)
{
    if ($value === null || $value === '') {
        return 0;
    }
    return is_numeric($value) ? $value : 0;
}


public function create(MainInvoiceRequest $request)
{
    $this->authorize('manage_system');

    // DB::beginTransaction();
    // try {

    //     $data = $request->except([
    //         'pilgrims',
    //         'ihramSupplies',
    //         'seatMapValidation',
    //         'discount',
    //         'tax',
    //         'paidAmount',
    //     ]);


      DB::beginTransaction();
    try {
        $busTrip = null;
        $seatMapArray = [];

        if ($request->filled('bus_trip_id') && $request->has('pilgrims')) {
            $busTrip = BusTrip::find($request->bus_trip_id);
            if (!$busTrip) {
                return response()->json(['message' => 'رحلة الباص غير موجودة'], 404);
            }

            $seatMapArray = json_decode(json_encode($busTrip->seatMap), true);
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

        $data = $request->except([
            'pilgrims',
            'ihramSupplies',
            'seatMapValidation',
            'discount',
            'tax',
            'paidAmount',
        ]);


        $data['discount'] = $this->ensureNumeric($request->input('discount', 0));
        $data['tax'] = $this->ensureNumeric($request->input('tax', 0));
        $data['paidAmount'] = $this->ensureNumeric($request->input('paidAmount', 0));


        $data['subtotal'] = 0;
        $data['totalAfterDiscount'] = 0;
        $data['total'] = 0;
        $data['creationDate'] = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $data['creationDateHijri'] = $this->getHijriDate();

        $data = array_merge($data, $this->prepareCreationMetaData());

        if ($request->filled('bus_trip_id') && $request->has('pilgrims')) {
            $busTrip = BusTrip::findOrFail($request->bus_trip_id);
            $this->validateBusSeats($busTrip, $request->pilgrims);
        }

        if ($request->has('roomNum')) {
            $this->validateRoomAvailability($request->hotel_id, $request->roomNum);
        }

        $invoice = MainInvoice::create($data);


        if ($request->has('hotels')) {
            $this->attachHotels($invoice, $request->hotels);
        }

        // if ($request->has('pilgrims')) {
        //     $this->attachPilgrims($invoice, $request->pilgrims);
        //     $invoice->pilgrimsCount = count($request->pilgrims);
        // }

        if ($request->has('pilgrims')) {
    $this->attachPilgrims($invoice, $request->pilgrims, $busTrip, $seatMapArray);
    $invoice->pilgrimsCount = count($request->pilgrims);
}


        if ($request->has('ihramSupplies')) {
            $this->attachIhramSupplies($invoice, $request->ihramSupplies);
        }

   $invoice->load(['hotels', 'ihramSupplies', 'pilgrims']);
        $invoice->calculateTotals();
        $invoice->updateIhramSuppliesCount();

        DB::commit();

        return response()->json([
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'invoice' => new MainInvoiceResource($invoice->load([
                'pilgrims',
                'ihramSupplies',
                'busTrip',
                'hotels',
                'campaign',
                'office',
                'group',
                'worker',
                'paymentMethodType',
                'mainPilgrim'
            ]))
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage(),
        ], 500);
    }
}



public function update(MainInvoiceRequest $request, $id)
{
    $this->authorize('manage_system');

    // DB::beginTransaction();
    // try {
    //     $invoice = MainInvoice::with(['pilgrims', 'ihramSupplies', 'hotels'])->findOrFail($id);

        DB::beginTransaction();
    try {
        $invoice = MainInvoice::with(['pilgrims', 'ihramSupplies', 'hotels', 'busTrip'])->findOrFail($id);
        $busTrip = $invoice->busTrip;
        $seatMapArray = $busTrip ? json_decode(json_encode($busTrip->seatMap), true) : [];
        $originalSeats = $invoice->pilgrims->pluck('pivot.seatNumber')->toArray();

        if ($request->has('pilgrims') && $busTrip) {
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
        ], $request->except([
            'pilgrims', 'ihramSupplies', 'seatMapValidation', 'hotels'
        ]), $this->prepareUpdateMetaData());

        $invoice->update($data);

        if ($request->has('hotels')) {
            $this->syncHotels($invoice, $request->hotels);
        }

        // if ($request->has('pilgrims')) {
        //     $this->syncPilgrims($invoice, $request->pilgrims);
        //     $invoice->pilgrimsCount = count($request->pilgrims);
        // }

        if ($request->has('pilgrims')) {
    $this->syncPilgrims($invoice, $request->pilgrims, $busTrip, $seatMapArray);
    $invoice->pilgrimsCount = count($request->pilgrims);
}


        if ($request->has('ihramSupplies')) {
            $this->syncIhramSupplies($invoice, $request->ihramSupplies);
        }

        $invoice->calculateTotals();
        $invoice->updateIhramSuppliesCount();

        DB::commit();

        return response()->json([
            'message' => 'تم تحديث الفاتورة بنجاح',
            'invoice' => new MainInvoiceResource(
                $invoice->load([
                    'pilgrims', 'ihramSupplies', 'busTrip', 'hotels', 'campaign', 'office', 'group', 'worker', 'paymentMethodType', 'mainPilgrim'
                ])
            )
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage(),
        ], 500);
    }

}

protected function attachPilgrims(MainInvoice $invoice, array $pilgrims, ?BusTrip $busTrip = null, ?array $seatMapArray = null)
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

        $seatNumbers = [];
        if (isset($pilgrim['seatNumber'])) {
            $seatNumbers = is_array($pilgrim['seatNumber'])
                ? $pilgrim['seatNumber']
                : explode(',', $pilgrim['seatNumber']);
        }

        // تحديث حالة المقاعد إذا كان هناك رحلة باص
        if ($busTrip && $seatMapArray && !empty($seatNumbers)) {
            foreach ($seatNumbers as $seatNumber) {
                try {
                    $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                } catch (\Exception $e) {
                    throw new \Exception("فشل في حجز المقعد {$seatNumber}: " . $e->getMessage());
                }
            }
        }

        $pilgrimsData[$p->id] = [
            'seatNumber' => implode(',', $seatNumbers),
            'status' => $pilgrim['status'] ?? null,
            'type' => $pilgrim['type'] ?? null,
            'position' => $pilgrim['position'] ?? null,
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->attach($pilgrimsData);

    // إرجاع البيانات بالتنسيق المطلوب
    return $invoice->pilgrims()->withPivot([
        'seatNumber',
        'status',
        'type',
        'position',
        'creationDate',
        'creationDateHijri'
    ])->get()->map(function($pilgrim) {
        return [
            'id' => $pilgrim->id,
            'name' => $pilgrim->name,
            'idNum' => $pilgrim->idNum,
            'phoNum' => $pilgrim->phoNum,
            'nationality' => $pilgrim->nationality,
            'gender' => $pilgrim->gender,
            'seatNumber' => $pilgrim->pivot->seatNumber,
            'status' => $pilgrim->pivot->status,
            'type' => $pilgrim->pivot->type,
            'position' => $pilgrim->pivot->position,
            'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
            'creationDate' => $pilgrim->pivot->creationDate
        ];
    })->toArray();
}

// protected function attachPilgrims(MainInvoice $invoice, array $pilgrims, ?BusTrip $busTrip = null, ?array $seatMapArray = null)
// {
//     $pilgrimsData = [];
//     $hijriDate = $this->getHijriDate();
//     $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

//     foreach ($pilgrims as $pilgrim) {
//         $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

//         $seatNumbers = [];
//         if (isset($pilgrim['seatNumber'])) {
//             $seatNumbers = is_array($pilgrim['seatNumber'])
//                 ? $pilgrim['seatNumber']
//                 : [$pilgrim['seatNumber']];
//         }

//         // تحديث حالة المقاعد إذا كان هناك رحلة باص
//         if ($busTrip && $seatMapArray && !empty($seatNumbers)) {
//             foreach ($seatNumbers as $seatNumber) {
//                 try {
//                     $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
//                 } catch (\Exception $e) {
//                     // يمكنك تسجيل الخطأ أو التعامل معه بطريقة أخرى
//                     throw new \Exception("فشل في حجز المقعد {$seatNumber}: " . $e->getMessage());
//                 }
//             }
//         }

//         $pilgrimsData[$p->id] = [
//             'seatNumber' => implode(',', $seatNumbers), // حفظ المقاعد كسلسلة نصية مفصولة بفواصل
//             'status' => $pilgrim['status'] ?? null,
//             'type' => $pilgrim['type'] ?? null,
//             'position' => $pilgrim['position'] ?? null,
//             'creationDate' => $currentDate,
//             'creationDateHijri' => $hijriDate,
//             'changed_data' => null
//         ];
//     }

//     $invoice->pilgrims()->attach($pilgrimsData);
// }


    protected function syncIhramSupplies(MainInvoice $invoice, array $supplies)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($invoice->ihramSupplies as $old) {
        $oldModel = $old;
        $oldModel->increment('quantity', $old->pivot->quantity);
    }

    $pivotData = [];

    foreach ($supplies as $supply) {
        $model = IhramSupply::findOrFail($supply['id']);

        if ($model->quantity <= 0 || $supply['quantity'] > $model->quantity) {
            throw new \Exception("الكمية غير متاحة لـ {$model->ihramItem->name}");
        }

        $model->decrement('quantity', $supply['quantity']);

        $total = $model->sellingPrice * $supply['quantity'];

        $pivotData[$supply['id']] = [
            'quantity' => $supply['quantity'],
            'price' => $model->sellingPrice,
            'total' => $total,
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
        ];
    }

    $invoice->ihramSupplies()->sync($pivotData);
}

protected function syncHotels(MainInvoice $invoice, array $hotelsData)
{
    $oldHotelPivots = $invoice->hotels()->get()->keyBy('id');
    $newPivotData = [];

    foreach ($hotelsData as $hotelData) {
        $hotel = Hotel::findOrFail($hotelData['hotel_id']);

        $roomNum = $hotelData['roomNum'] ?? null;
        $hotelId = $hotel->id;

        if ($roomNum) {
            $this->validateRoomAvailability($hotelId, $roomNum);
        }

        $existingPivot = $oldHotelPivots->get($hotelId);
        $oldRoom = $existingPivot?->pivot->roomNum;

        if ($existingPivot && $oldRoom && $roomNum && $oldRoom !== $roomNum) {
            $this->releaseRoom($hotel, $oldRoom);
            $this->occupyRoom($hotel, $roomNum);
        }

        if (!$existingPivot && $roomNum) {
            $this->occupyRoom($hotel, $roomNum);
        }

        $newPivotData[$hotelId] = [
            'checkInDate' => $hotelData['checkInDate'] ?? null,
            'checkOutDate' => $hotelData['checkOutDate'] ?? null,
            'checkInDateHijri' => $hotelData['checkInDateHijri'] ?? null,
            'checkOutDateHijri' => $hotelData['checkOutDateHijri'] ?? null,
            'numBed' => $hotelData['numBed'] ?? null,
            'numRoom' => $hotelData['numRoom'] ?? null,
            'bookingSource' => $hotelData['bookingSource'] ?? null,
            'roomNum' => $roomNum,
            'need' => $hotelData['need'] ?? null,
            'sleep' => $hotelData['sleep'] ?? null,
            'numDay' => $hotelData['numDay'] ?? 1,
            'hotelSubtotal' => $invoice->calculateHotelTotalForPivot($hotel, $hotelData),
        ];
    }


    foreach ($oldHotelPivots as $oldHotelId => $oldHotel) {
        if (!isset($newPivotData[$oldHotelId])) {
            $roomNum = $oldHotel->pivot->roomNum ?? null;
            if ($roomNum) {
                $this->releaseRoom($oldHotel, $roomNum);
            }
        }
    }

    $invoice->hotels()->sync($newPivotData);
}


protected function occupyRoom(Hotel $hotel, string $roomNum): void
{
    $availableRooms = $hotel->roomNum ?? [];

    if (!in_array($roomNum, $availableRooms)) {
        throw new \Exception("الغرفة {$roomNum} غير متاحة للحجز.");
    }

    $updatedRooms = array_values(array_diff($availableRooms, [$roomNum]));
    $hotel->roomNum = $updatedRooms;
    $hotel->save();
}

protected function releaseRoom(Hotel $hotel, string $roomNum): void
{
    $currentRooms = $hotel->roomNum ?? [];

    if (!in_array($roomNum, $currentRooms)) {
        $currentRooms[] = $roomNum;
        sort($currentRooms);
        $hotel->roomNum = $currentRooms;
        $hotel->save();
    }
}


protected function attachHotels(MainInvoice $invoice, array $hotelsData)
{
    foreach ($hotelsData as $hotelData) {
        $hotel = Hotel::findOrFail($hotelData['hotel_id']);

        if (isset($hotelData['roomNum'])) {
            $this->validateRoomAvailability($hotel->id, $hotelData['roomNum']);

            $this->occupyRoom($hotel, $hotelData['roomNum']);
        }

        $invoice->hotels()->attach($hotel->id, [
            'checkInDate' => $hotelData['checkInDate'] ?? null,
            'checkOutDate' => $hotelData['checkOutDate'] ?? null,
            'checkInDateHijri' => $hotelData['checkInDateHijri'] ?? null,
            'checkOutDateHijri' => $hotelData['checkOutDateHijri'] ?? null,
            'numBed' => $hotelData['numBed'] ?? null,
            'numRoom' => $hotelData['numRoom'] ?? null,
            'bookingSource' => $hotelData['bookingSource'] ?? null,
            'roomNum' => $hotelData['roomNum'] ?? null,
            'need' => $hotelData['need'] ?? null,
            'sleep' => $hotelData['sleep'] ?? null,
            'numDay' => $hotelData['numDay'] ?? 1,
            'hotelSubtotal' => $invoice->calculateHotelTotalForPivot($hotel, $hotelData),

        ]);
    }
}


protected function attachIhramSupplies(MainInvoice $invoice, array $supplies)
{
    foreach ($supplies as $supply) {
        $model = IhramSupply::findOrFail($supply['id']);

        if ($model->quantity <= 0 || $supply['quantity'] > $model->quantity) {
            throw new \Exception("الكمية غير متاحة لـ {$model->ihramItem->name}");
        }
        $model->decrement('quantity', $supply['quantity']);

        $total = $model->sellingPrice * $supply['quantity'];

        $invoice->ihramSupplies()->attach($supply['id'], [
            'quantity' => $supply['quantity'],
            'price' => $model->sellingPrice,
            'total' => $total,
            'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            'creationDateHijri' => $this->getHijriDate(),
        ]);
    }
}

protected function validateRoomAvailability($hotelId, $roomNum)
{
    $hotel = Hotel::findOrFail($hotelId);
    $availableRooms = $hotel->roomNum ?? [];

    if (!in_array($roomNum, $availableRooms)) {
        throw new \Exception('الغرفة غير متاحة للحجز');
    }

    $hotel->roomNum = array_values(array_diff($availableRooms, [$roomNum]));
    $hotel->save();
}


        public function edit(string $id)
    {
        $this->authorize('manage_system');

        $MainInvoice =MainInvoice::with([
        'pilgrims','mainPilgrim'
    ])->find($id);

        if (!$MainInvoice) {
            return response()->json(['message' => "Main Invoice not found."], 404);
        }
        return $this->respondWithResource($MainInvoice, "Main Invoice retrieved for editing.");
    }

    protected function updateSeatStatusInTrip($busTrip, $seatNumber, $status)
{
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

// protected function syncPilgrims(MainInvoice $invoice, array $pilgrims)
// {
//     $hijriDate = $this->getHijriDate();
//     $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
//     $pilgrimsData = [];

//     $oldPivotPilgrims = $invoice->pilgrims->mapWithKeys(function ($pilgrim) {
//         return [
//             $pilgrim->id => [
//                 'creationDate' => $pilgrim->pivot->creationDate,
//                 'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
//                 'seatNumber' => $pilgrim->pivot->seatNumber,
//                 'status' => $pilgrim->pivot->status,
//                 'type' => $pilgrim->pivot->type,
//                 'position' => $pilgrim->pivot->position,
//             ],
//         ];
//     })->toArray();

//     foreach ($pilgrims as $pilgrim) {
//         $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

//         $seatNumber = null;
//         if (isset($pilgrim['seatNumber'])) {
//             $seatNumber = is_array($pilgrim['seatNumber'])
//                 ? implode(',', $pilgrim['seatNumber'])
//                 : $pilgrim['seatNumber'];
//         }

//         $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $p->id)->first();

//         $pilgrimsData[$p->id] = [
//             'seatNumber' => $seatNumber,
//             'status' => $pilgrim['status'] ?? null,
//             'type' => $pilgrim['type'] ?? null,
//             'position' => $pilgrim['position'] ?? null,
//             'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
//             'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
//             'changed_data' => null,
//         ];
//     }

//     // تتبع التغييرات
//     $pivotChanges = $this->getPivotChanges($oldPivotPilgrims, $pilgrimsData);
//     foreach ($pivotChanges as $pilgrimId => $change) {
//         if (isset($pilgrimsData[$pilgrimId])) {
//             $pilgrimsData[$pilgrimId]['changed_data'] = json_encode($change, JSON_UNESCAPED_UNICODE);
//         }
//     }

//     $invoice->pilgrims()->sync($pilgrimsData);
// }

protected function syncPilgrims(MainInvoice $invoice, array $pilgrims, ?BusTrip $busTrip = null, ?array $seatMapArray = null)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    $oldPivotPilgrims = $invoice->pilgrims->mapWithKeys(function ($pilgrim) {
        return [
            $pilgrim->id => [
                'creationDate' => $pilgrim->pivot->creationDate,
                'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
                'seatNumber' => $pilgrim->pivot->seatNumber,
                'status' => $pilgrim->pivot->status,
                'type' => $pilgrim->pivot->type,
                'position' => $pilgrim->pivot->position,
            ],
        ];
    })->toArray();

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

        $seatNumber = null;
        if (isset($pilgrim['seatNumber'])) {
            $seatNumber = is_array($pilgrim['seatNumber'])
                ? implode(',', $pilgrim['seatNumber'])
                : $pilgrim['seatNumber'];

            // تحديث حالة المقعد إذا كان هناك رحلة باص
            if ($busTrip && $seatMapArray) {
                $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
            }
        }

        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $p->id)->first();

        $pilgrimsData[$p->id] = [
            'seatNumber' => $seatNumber,
            'status' => $pilgrim['status'] ?? null,
            'type' => $pilgrim['type'] ?? null,
            'position' => $pilgrim['position'] ?? null,
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null,
        ];
    }

    // تتبع التغييرات
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



protected function validateBusSeats(BusTrip $busTrip, array $pilgrims)
{
    $requestedSeats = [];
    foreach ($pilgrims as $pilgrim) {
        if (isset($pilgrim['seatNumber'])) {
            $seats = is_array($pilgrim['seatNumber'])
                ? $pilgrim['seatNumber']
                : [$pilgrim['seatNumber']];
            $requestedSeats = array_merge($requestedSeats, $seats);
        }
    }

    $availableSeats = collect($busTrip->seatMap)
        ->where('status', 'available')
        ->pluck('seatNumber')
        ->toArray();

    $unavailableSeats = array_diff($requestedSeats, $availableSeats);

    if (!empty($unavailableSeats)) {
        throw new \Exception("المقاعد التالية غير متاحة: " . implode(', ', $unavailableSeats));
    }
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
