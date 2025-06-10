<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bus;
use App\Models\Worker;
use App\Models\BusInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use Illuminate\Http\JsonResponse;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\BusInvoiceRequest;
use App\Http\Resources\Admin\BusInvoiceResource;

class BusInvoiceController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;


        public function showAll(Request $request)
    {
        try {
            $query = BusInvoice::with([
                'bus', 'trip', 'campaign', 'office', 'group',
                'busDriver', 'worker', 'paymentMethodType', 'pilgrims'
            ]);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('paymentStatus')) {
                $query->where('paymentStatus', $request->paymentStatus);
            }

            if ($request->has('bus_id')) {
                $query->where('bus_id', $request->bus_id);
            }

            if ($request->has('trip_id')) {
                $query->where('trip_id', $request->trip_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('travelDate', [$request->date_from, $request->date_to]);
            }

            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_dir', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $invoices = $query->paginate($request->get('per_page', 15));

            return $this->respondWithCollection($invoices->getCollection(), 'Bus invoices retrieved successfully');

        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to retrieve bus invoices');
        }
    }



public function create(BusInvoiceRequest $request): JsonResponse
{
    DB::beginTransaction();

    try {
        $validated = $request->validated();


$workerBelongsToCampaign = DB::table('campaign_workers')
    ->where('campaign_id', $request->campaign_id)
    ->where('worker_id', $request->worker_id)
    ->exists();

if (!$workerBelongsToCampaign) {
    return response()->json([

        'message' => 'المندوب لا يتبع هذه الحملة.'
    ]);
}

        $pilgrims = $validated['pilgrims'] ?? [];
        if (empty($pilgrims)) {
            return response()->json([
    'success' => false,
    'message' => 'يجب تحديد المعتمرين والمقاعد',
], 400);

        }

$bus = Bus::findOrFail($validated['bus_id']);
        $invoiceData = array_merge([
            'main_pilgrim_id'         => $validated['main_pilgrim_id'] ?? null,
            'trip_id'                 => $validated['trip_id'],
            'campaign_id'             => $validated['campaign_id'],
            'office_id'               => $validated['office_id'],
            'group_id'                => $validated['group_id'],
            'bus_id'                  => $validated['bus_id'],
            'bus_driver_id'           => $validated['bus_driver_id'],
            'worker_id'               => $validated['worker_id'],
            'payment_method_type_id'  => $validated['payment_method_type_id'],
            'travelDate'              => $validated['travelDate'] ,
            'travelDateHijri'         => $validated['travelDateHijri'],
            'discount'                => $validated['discount'] ?? 0,
            'tax'                     => $validated['tax'] ?? 0,
            'paidAmount'              => $validated['paidAmount'],
            'invoiceStatus'=> $validated['invoiceStatus'] ?? 'pending',
            'paymentStatus'           => $validated['paymentStatus'] ?? 'pending',
            'reason'                  => $validated['reason'] ?? null,
                'seatMap'                 => $bus->seatMap,
        ], $this->prepareCreationMetaData());


        $invoice = BusInvoice::create($invoiceData);

        $this->validateAndAttachPilgrims($invoice, $pilgrims);

        $invoice->calculateTotal();
        $invoice->updateSeatsCount();
         $invoice->updateSeatMapAfterBooking();

        $invoice->load([
            'mainPilgrim', 'trip', 'campaign', 'office', 'group',
            'bus', 'busDriver', 'worker', 'paymentMethodType',
            'pilgrims'
        ]);

        DB::commit();

        return $this->respondWithResource($invoice, 'تم إنشاء فاتورة الباص بنجاح.');
    } catch (\Throwable $e) {
        DB::rollBack();
        return $this->handleError($e, 'حدث خطأ أثناء إنشاء فاتورة الباص.');
    }
}
protected function validateAndAttachPilgrims(BusInvoice $invoice, array $pilgrimsData): void
{
    $seatMap = collect($invoice->seatMap); // استخدام موحد

    $pilgrimsToAttach = [];
    $seatNumbersUsed = [];

    foreach ($pilgrimsData as $pilgrim) {
        throw_unless(
            isset($pilgrim['id'], $pilgrim['seatNumber']),
            new \Exception("بيانات المعتمر غير صالحة")
        );

        $seatNumber = strtoupper(trim($pilgrim['seatNumber']));

        // ابحث عن المقعد
        $seatIndex = $seatMap->search(function ($seat) use ($seatNumber) {
            return strtoupper(trim($seat['seatNumber'])) === $seatNumber;
        });

        throw_unless(
            $seatIndex !== false && ($seatMap[$seatIndex]['status'] ?? '') === 'available',
            new \Exception("المقعد رقم {$seatNumber} غير متاح أو غير موجود.")
        );

        throw_if(
            in_array($seatNumber, $seatNumbersUsed),
            new \Exception("المقعد رقم {$seatNumber} تم استخدامه أكثر من مرة.")
        );

$seatNumbersUsed = [];
$pilgrimsToAttach = [];

$seatMap = collect($invoice->seatMap); // جمع المقاعد الأصلية

foreach ($pilgrimsData as $pilgrim) {
    throw_unless(
        isset($pilgrim['id'], $pilgrim['seatNumber']),
        new \Exception("بيانات المعتمر غير صالحة")
    );

    $seatNumber = strtoupper(trim($pilgrim['seatNumber']));

    $seatExists = $seatMap->firstWhere('seatNumber', $seatNumber);

    throw_unless(
        $seatExists && ($seatExists['status'] ?? '') === 'available',
        new \Exception("المقعد رقم {$seatNumber} غير متاح أو غير موجود.")
    );

    throw_if(
        in_array($seatNumber, $seatNumbersUsed),
        new \Exception("المقعد رقم {$seatNumber} تم استخدامه أكثر من مرة.")
    );

    $seatNumbersUsed[] = $seatNumber;

    $pilgrimsToAttach[$pilgrim['id']] = [
        'seatNumber' => $seatNumber,
        'seatPrice' => $pilgrim['seatPrice'] ?? 0,
        'status' => $pilgrim['status'] ?? 'booked',
        'creationDate' => now(),
        'creationDateHijri' => $this->getHijriDate(),
        'type' => $pilgrim['type'] ?? null,
        'position' => $pilgrim['position'] ?? null,
    ];
}

// تحديث الحالة داخل الـ seatMap عبر map()
$updatedSeatMap = $seatMap->map(function ($seat) use ($seatNumbersUsed) {
    if (in_array(strtoupper(trim($seat['seatNumber'])), $seatNumbersUsed)) {
        $seat['status'] = 'booked';
    }
    return $seat;
});

// ربط المعتمرين
$invoice->pilgrims()->attach($pilgrimsToAttach);

// تحديث الـ seatMap داخل الفاتورة
$invoice->update([
    'seatMap' => $updatedSeatMap->values()->all(), // تأكد إنها indexed array
]);
    }
}





    public function edit(BusInvoice $busInvoice)
    {
        try {
            $this->loadCreatorRelations($busInvoice);
            $this->loadUpdaterRelations($busInvoice);

            $busInvoice->load([
                'bus', 'trip', 'campaign', 'office', 'group',
                'busDriver', 'worker', 'paymentMethodType', 'pilgrims'
            ]);

            return $this->respondWithResource($busInvoice, 'Bus invoice retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to retrieve bus invoice');
        }
    }


    public function update(BusInvoiceRequest $request, BusInvoice $busInvoice)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            $updateData = $this->prepareUpdateMeta($request);
            $validated = array_merge($validated, $updateData);

            $oldData = $busInvoice->toArray();

            $busInvoice->update($validated);

            if ($request->has('pilgrims')) {
                $pilgrimsData = [];
                foreach ($request->pilgrims as $pilgrim) {
                    $pilgrimsData[$pilgrim['id']] = [
                        'seatNumber' => $pilgrim['seatNumber'],
                        'seatPrice' => $pilgrim['seatPrice'],
                        'status' => $pilgrim['status'] ?? 'booked',
                        'updated_at' => now()
                    ];
                }

                $busInvoice->pilgrims()->sync($pilgrimsData);
            }

            $busInvoice->calculateTotal();
            $busInvoice->updateSeatsCount();

            $this->applyChangesAndSave($busInvoice, $validated, $oldData);

            DB::commit();

            return $this->respondWithResource($busInvoice, 'Bus invoice updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleError($e, 'Failed to update bus invoice');
        }
    }


    public function addPilgrim(Request $request, BusInvoice $busInvoice)
    {
        $request->validate([
            'pilgrim_id' => 'required|exists:pilgrims,id',
            'seatNumber' => 'required|string',
            'seatPrice' => 'required|numeric|min:0',
        ]);

        $availableSeats = $busInvoice->available_seats;
        if (!in_array($request->seatNumber, array_column($availableSeats, 'seatNumber'))) {
            return response()->json([
                'success' => false,
                'message' => 'Seat is not available or does not exist'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $busInvoice->pilgrims()->attach($request->pilgrim_id, [
                'seatNumber' => $request->seatNumber,
                'seatPrice' => $request->seatPrice,
                'status' => 'booked',
                'creationDate' => now(),
                'creationDateHijri' => $this->getHijriDate()
            ]);

            $busInvoice->calculateTotal();
            $busInvoice->updateSeatsCount();

            DB::commit();

            return $this->respondWithResource($busInvoice, 'Pilgrim added to bus invoice successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleError($e, 'Failed to add pilgrim to bus invoice');
        }
    }


    public function removePilgrim(Request $request, BusInvoice $busInvoice, Pilgrim $pilgrim)
    {
        DB::beginTransaction();

        try {
            $busInvoice->pilgrims()->detach($pilgrim->id);

            $busInvoice->calculateTotal();
            $busInvoice->updateSeatsCount();

            DB::commit();

            return $this->respondWithResource($busInvoice, 'Pilgrim removed from bus invoice successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleError($e, 'Failed to remove pilgrim from bus invoice');
        }
    }


    public function updatePaymentStatus(Request $request, BusInvoice $busInvoice)
    {
        try {
            $request->validate([
                'paymentStatus' => 'required|in:pending,paid,refunded',
                'paidAmount' => 'required|numeric|min:0'
            ]);

            $oldData = $busInvoice->toArray();

            $busInvoice->update([
                'paymentStatus' => $request->paymentStatus,
                'paidAmount' => $request->paidAmount
            ]);

            $this->applyChangesAndSave($busInvoice, $request->all(), $oldData);

            return $this->respondWithResource($busInvoice, 'Payment status updated successfully');
        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to update payment status');
        }
    }

        protected function getResourceClass(): string
    {
        return BusInvoiceResource::class;
    }
}
