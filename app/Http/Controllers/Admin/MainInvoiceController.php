<?php

namespace App\Http\Controllers\Admin;

use App\Models\Hotel;
use App\Models\BusTrip;
use App\Models\Pilgrim;
use App\Models\IhramSupply;
use App\Models\MainInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
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

        if ($request->filled('worker_id')) {
            $query->where('worker_id', $request->worker_id);
        }
        if ($request->filled('payment_method_type_id')) {
            $query->where('payment_method_type_id', $request->payment_method_type_id);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $MainInvoices = $query->orderBy('created_at', 'desc')->paginate(10);
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

        if ($request->filled('worker_id')) {
            $query->where('worker_id', $request->worker_id);
        }
        if ($request->filled('payment_method_type_id')) {
            $query->where('payment_method_type_id', $request->payment_method_type_id);
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

        $MainInvoices = $query->orderBy('created_at', 'desc')->get();
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

        if ($request->has('pilgrims')) {
$attachedPilgrims = $this->attachPilgrims($invoice, $request->pilgrims, $busTrip, $seatMapArray);
$invoice->pilgrimsCount = count($attachedPilgrims);
}

        if ($request->has('ihramSupplies')) {
            $this->attachIhramSupplies($invoice, $request->ihramSupplies);
        }

   $invoice->load(['hotels', 'ihramSupplies', 'pilgrims']);
        $invoice->updateSeatsCount();
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

    DB::beginTransaction();
    try {
        $invoice = MainInvoice::with(['pilgrims', 'ihramSupplies', 'hotels', 'busTrip'])->findOrFail($id);

        // التحقق من تطابق paidAmount مع total
        if ($request->has('paidAmount')) {
            $tempInvoice = clone $invoice;
            $tempInvoice->fill([
                'discount' => $request->input('discount', $invoice->discount),
                'tax' => $request->input('tax', $invoice->tax),
                'paidAmount' => $request->input('paidAmount', $invoice->paidAmount)
            ]);
            $tempInvoice->calculateTotals();

            if (abs($request->paidAmount - $tempInvoice->total) > 0.01) {
                return response()->json([
                    'message' => 'المبلغ المدفوع يجب أن يساوي الإجمالي',
                    'required_amount' => $tempInvoice->total,
                    'paid_amount' => $request->paidAmount
                ], 422);
            }
        }

        $busTrip = $invoice->busTrip;
        $seatMapArray = $busTrip ? json_decode(json_encode($busTrip->seatMap), true) : [];

        // الحصول على المقاعد الأصلية
        $originalSeats = $invoice->pilgrims->mapWithKeys(function($pilgrim) {
            return [$pilgrim->id => $pilgrim->pivot->seatNumber];
        })->toArray();

        // التحقق من التغييرات في الحجاج
        $pilgrimsChanged = false;
        if ($request->has('pilgrims') && $busTrip) {
            $newPilgrimsData = collect($request->pilgrims)->keyBy('idNum');
            $originalPilgrimsData = $invoice->pilgrims->keyBy('idNum');

            $pilgrimsChanged = $this->hasPilgrimsOrSeatsChanged($originalPilgrimsData, $newPilgrimsData);

            if ($pilgrimsChanged) {
                $requestedSeats = collect($request->pilgrims)
                    ->pluck('seatNumber')
                    ->flatten()
                    ->filter()
                    ->toArray();

                $availableSeats = collect($seatMapArray)
                    ->where('status', 'available')
                    ->pluck('seatNumber')
                    ->toArray();

                $originalSeatsFlat = collect($originalSeats)->flatten()->toArray();
                $allAvailableSeats = array_unique(array_merge($availableSeats, $originalSeatsFlat));

                $unavailableSeats = array_diff($requestedSeats, $allAvailableSeats);

                if (!empty($unavailableSeats)) {
                    return response()->json([
                        'message' => 'بعض المقاعد غير متوفرة',
                        'unavailable_seats' => array_values($unavailableSeats)
                    ], 422);
                }
            }
        }

        // تحديث البيانات الأساسية
        $data = $request->except(['pilgrims', 'ihramSupplies', 'seatMapValidation', 'hotels']);

        $numericFields = [
            'discount' => $request->input('discount', 0),
            'tax' => $request->input('tax', 0),
            'paidAmount' => $request->input('paidAmount', 0)
        ];

        foreach ($numericFields as $field => $value) {
            $data[$field] = $this->ensureNumeric($value);
        }

        $data = array_merge($data, $this->prepareUpdateMetaData());
        $invoice->update($data);

        // تحديث العلاقات
        if ($request->has('hotels')) {
            $this->syncHotels($invoice, $request->hotels);
        }

        if ($pilgrimsChanged) {
            $syncedPilgrims = $this->syncPilgrims($invoice, $request->pilgrims, $busTrip, $seatMapArray);
            $invoice->pilgrimsCount = count($syncedPilgrims);
        }

        if ($request->has('ihramSupplies')) {
            $this->syncIhramSupplies($invoice, $request->ihramSupplies);
        }

        // تحديث الحسابات
        $invoice->updateSeatsCount();
        $invoice->calculateTotals();
        $invoice->updateIhramSuppliesCount();

        DB::commit();

        return response()->json([
            'message' => 'تم تحديث الفاتورة بنجاح',
            'invoice' => new MainInvoiceResource(
                $invoice->load([
                    'pilgrims', 'ihramSupplies', 'busTrip', 'hotels',
                    'campaign', 'office', 'group', 'worker',
                    'paymentMethodType', 'mainPilgrim'
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


protected function syncPilgrims(MainInvoice $invoice, array $pilgrims, ?BusTrip $busTrip = null, ?array $seatMapArray = null)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    if ($busTrip) {
        $oldPilgrims = $invoice->pilgrims()->withPivot('seatNumber')->get();
        foreach ($oldPilgrims as $oldPilgrim) {
            $oldSeats = explode(',', $oldPilgrim->pivot->seatNumber);
            foreach ($oldSeats as $seat) {
                if (!empty($seat)) {
                    $this->updateSeatStatusInTrip($busTrip, $seat, 'available');
                }
            }
        }
    }

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

        $seatNumbers = [];
        $seatTypes = [];
        $seatPositions = [];

        if (isset($pilgrim['seatNumber'])) {
            $seatNumbers = is_array($pilgrim['seatNumber'])
                ? $pilgrim['seatNumber']
                : explode(',', $pilgrim['seatNumber']);
        }

        if ($busTrip && $seatMapArray && !empty($seatNumbers)) {
            foreach ($seatNumbers as $seatNumber) {
                try {
                    $seatInfo = collect($busTrip->seatMap)->firstWhere('seatNumber', $seatNumber);

                    if (!$seatInfo) {
                        throw new \Exception("المقعد {$seatNumber} غير موجود في رحلة الباص");
                    }

                    $seatTypes[] = $seatInfo['type'] ?? null;
                    $seatPositions[] = $seatInfo['position'] ?? null;

                    $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                } catch (\Exception $e) {
                    throw new \Exception("فشل في حجز المقعد {$seatNumber}: " . $e->getMessage());
                }
            }
        }

        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $p->id)->first();

        $pilgrimsData[$p->id] = [
            'seatNumber' => implode(',', $seatNumbers),
            'status' => 'booked',
            'type' => implode(',', array_unique($seatTypes)),
            'position' => implode(',', array_unique($seatPositions)),
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null,
        ];
    }

    $invoice->pilgrims()->sync($pilgrimsData);

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


protected function attachPilgrims(MainInvoice $invoice, array $pilgrims, ?BusTrip $busTrip = null, ?array $seatMapArray = null)
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

        $seatNumbers = [];
        $seatTypes = [];
        $seatPositions = [];

        if (isset($pilgrim['seatNumber'])) {
            $seatNumbers = is_array($pilgrim['seatNumber'])
                ? $pilgrim['seatNumber']
                : explode(',', $pilgrim['seatNumber']);
        }

        if ($busTrip && $seatMapArray && !empty($seatNumbers)) {
            foreach ($seatNumbers as $seatNumber) {
                try {
                    $seatInfo = collect($busTrip->seatMap)->firstWhere('seatNumber', $seatNumber);

                    if (!$seatInfo) {
                        throw new \Exception("المقعد {$seatNumber} غير موجود في رحلة الباص");
                    }

                    $seatTypes[] = $seatInfo['type'] ?? null;
                    $seatPositions[] = $seatInfo['position'] ?? null;

                    $this->updateSeatStatusInTrip($busTrip, $seatNumber, 'booked');
                } catch (\Exception $e) {
                    throw new \Exception("فشل في حجز المقعد {$seatNumber}: " . $e->getMessage());
                }
            }
        }

        $pilgrimsData[$p->id] = [
            'seatNumber' => implode(',', $seatNumbers),
            'status' => 'booked',
            'type' => implode(',', array_unique($seatTypes)),
            'position' => implode(',', array_unique($seatPositions)),
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->attach($pilgrimsData);

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
          if ($model->quantity <= 5) {
            Log::warning("المنتج {$model->ihramItem->name} أوشك على النفاد. الكمية الحالية: {$model->quantity}");
        }

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
          if ($model->quantity <= 5) {
            Log::warning("المنتج {$model->ihramItem->name} أوشك على النفاد. الكمية الحالية: {$model->quantity}");
        }

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

protected function hasPilgrimsOrSeatsChanged($originalPilgrims, $newPilgrims)
{

    if ($originalPilgrims->count() !== $newPilgrims->count()) {
        return true;
    }

    foreach ($newPilgrims as $idNum => $newPilgrim) {
        if (!isset($originalPilgrims[$idNum])) {
            return true;
        }

        $originalPilgrim = $originalPilgrims[$idNum];

        $newSeats = is_array($newPilgrim['seatNumber']) ?
            $newPilgrim['seatNumber'] :
            explode(',', $newPilgrim['seatNumber']);

        $originalSeats = is_array($originalPilgrim->pivot->seatNumber) ?
            $originalPilgrim->pivot->seatNumber :
            explode(',', $originalPilgrim->pivot->seatNumber);

        sort($newSeats);
        sort($originalSeats);

        if ($newSeats !== $originalSeats) {
            return true;
        }
    }

    return false;
}

// protected function hasPilgrimsChanges(MainInvoice $invoice, array $newPilgrims): bool
// {
//     // الحصول على المعتمرين الحاليين مع أرقام مقاعدهم
//     $current = $invoice->pilgrims->map(function ($p) {
//         return [
//             'idNum' => $p->idNum,
//             'phoNum' => $p->phoNum,
//             'seatNumbers' => collect($p->pivot->seatNumber ?? [])->sort()->values()->all(),
//         ];
//     })->toArray();

//     // ترتيبهم لتسهيل المقارنة
//     usort($current, fn($a, $b) => $a['idNum'] <=> $b['idNum']);

//     $new = collect($newPilgrims)->map(function ($p) {
//         return [
//             'idNum' => $p['idNum'] ?? null,
//             'phoNum' => $p['phoNum'] ?? null,
//             'seatNumbers' => collect($p['seatNumber'] ?? [])->sort()->values()->all(),
//         ];
//     })->toArray();

//     usort($new, fn($a, $b) => $a['idNum'] <=> $b['idNum']);

//     return $current !== $new;
// }


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
    // التحقق من وجود رقم الهوية (إلزامي للجميع)
    if (empty($pilgrimData['idNum'])) {
        throw new \InvalidArgumentException('رقم الهوية مطلوب لجميع الحجاج. بيانات الحاج: ' . json_encode($pilgrimData));
    }

    // التحقق من البيانات الأساسية المطلوبة
    $requiredFields = ['name', 'nationality', 'gender'];
    foreach ($requiredFields as $field) {
        if (empty($pilgrimData[$field])) {
            throw new \InvalidArgumentException("حقل {$field} مطلوب لتسجيل الحاج. رقم الهوية: {$pilgrimData['idNum']}");
        }
    }

    // البحث عن الحاج أو إنشائه مع التحقق من التحديثات
    return Pilgrim::updateOrCreate(
        ['idNum' => $pilgrimData['idNum']],
        [
            'name' => $pilgrimData['name'],
            'nationality' => $pilgrimData['nationality'],
            'gender' => $pilgrimData['gender'],
            'phoNum' => $pilgrimData['phoNum'] ?? null
        ]
    );
}

// protected function findOrCreatePilgrimForInvoice(array $pilgrimData): Pilgrim
// {
//     if (empty($pilgrimData['idNum'])) {
//         throw new \Exception('رقم الهوية (idNum) مطلوب لكل معتمر بما فيهم الأطفال.');
//     }
//     $pilgrim = Pilgrim::where('idNum', $pilgrimData['idNum'])->first();
//     if (!$pilgrim) {
//         if (!isset($pilgrimData['name'], $pilgrimData['nationality'], $pilgrimData['gender'])) {
//             throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع على الأقل');
//         }
//         return Pilgrim::create([
//             'idNum'        => $pilgrimData['idNum'],
//             'name'         => $pilgrimData['name'],
//             'nationality'  => $pilgrimData['nationality'],
//             'gender'       => $pilgrimData['gender'],
//             'phoNum'       => $pilgrimData['phoNum'] ?? null
//         ]);
//     }
//     $updates = [];
//     if (!empty($pilgrimData['name']) && $pilgrim->name !== $pilgrimData['name']) {
//         $updates['name'] = $pilgrimData['name'];
//     }
//     if (!empty($pilgrimData['nationality']) && $pilgrim->nationality !== $pilgrimData['nationality']) {
//         $updates['nationality'] = $pilgrimData['nationality'];
//     }
//     if (!empty($pilgrimData['gender']) && $pilgrim->gender !== $pilgrimData['gender']) {
//         $updates['gender'] = $pilgrimData['gender'];
//     }
//     if (!empty($pilgrimData['phoNum']) && $pilgrim->phoNum !== $pilgrimData['phoNum']) {
//         $updates['phoNum'] = $pilgrimData['phoNum'];
//     }

//     if (!empty($updates)) {
//         $pilgrim->update($updates);
//     }
//     return $pilgrim;
// }

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

    // public function rejected($id, Request $request)
    // {
    //     $invoice = MainInvoice::find($id);
    //     return $this->changeInvoiceStatus($invoice, 'rejected', [
    //         'reason' => $request->input('reason'),
    //     ]);

    // }

public function rejected($id, Request $request)
{
    $invoice = MainInvoice::findOrFail($id);
    
    $response = $this->changeInvoiceStatus($invoice, 'rejected', [
        'reason' => $request->input('reason'),
    ]);

    try {
        $vonageService = new VonageService();
        $adminNumber = config('services.vonage.admin_number');
        
        $message = $this->prepareRejectionMessage($invoice, $request->input('reason'));
        $result = $vonageService->sendWhatsAppMessage($adminNumber, $message);

        if (!$result['success']) {
            Log::warning('فشل إرسال إشعار واتساب لرفض الفاتورة', [
                'invoice_id' => $id,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }

        return $response;

    } catch (\Exception $e) {
        Log::error('خطأ غير متوقع في إرسال إشعار الرفض', [
            'invoice_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'data' => $invoice,
            'message' => 'تم رفض الفاتورة ولكن حدث خطأ في إرسال الإشعار',
            'whatsapp_error' => true,
            'error_details' => $e->getMessage()
        ], 200);
    }
}

protected function prepareRejectionMessage($invoice, $reason): string
{
    return sprintf(
        "🚨 *إشعار رفض فاتورة*\n\n".
        "📌 رقم الفاتورة: %s\n".
        "🏢 المكتب: %s\n".
        "👤 المسؤول: %s\n".
        "💵 المبلغ الإجمالي: %s ر.س\n".
        "📅 تاريخ الإنشاء: %s\n".
        "🛑 السبب: %s\n\n".
        "مع تحيات نظام إدارة الفواتير",
        $invoice->invoiceNumber,
        $invoice->office_name ?? 'غير معروف',
        $invoice->worker_name ?? 'غير معروف',
        $invoice->total ?? '0.00',
        $invoice->creationDateHijri ?? 'غير معروف',
        $reason ?? 'غير محدد'
    );
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
