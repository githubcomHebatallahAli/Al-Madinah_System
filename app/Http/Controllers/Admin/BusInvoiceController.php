<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bus;
use App\Models\Worker;
use App\Models\BusTrip;
use App\Models\BusInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use Illuminate\Http\JsonResponse;
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

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('paymentStatus', $request->payment_status);
        }

        if ($request->filled('invoice_status')) {
            $query->where('invoiceStatus', $request->invoice_status);
        }

        $busInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'data' => ShowAllBusInvoiceResource::collection($busInvoices),
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

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        $busInvoices = $query->with(['busTrip'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => ShowAllBusInvoiceResource::collection($busInvoices),
            'message' => "Show All Bus Invoices."
        ]);
    }

public function create(BusInvoiceRequest $request)
{
    $this->authorize('manage_system');

    // التحقق من توفر المقاعد
    if ($request->has('bus_trip_id')) {
        $busTrip = BusTrip::findOrFail($request->bus_trip_id);

        if ($request->has('pilgrims')) {
            $requestedSeats = collect($request->pilgrims)->pluck('seatNumber');
            $availableSeats = collect($busTrip->seatMap)
                ->where('status', 'available')
                ->pluck('seatNumber');

            $unavailableSeats = $requestedSeats->diff($availableSeats);

            if ($unavailableSeats->isNotEmpty()) {
                return response()->json([
                    'message' => 'Some seats are not available',
                    'unavailable_seats' => $unavailableSeats
                ], 422);
            }
        }
    }

    // تعيين القيم الافتراضية
    $data = array_merge([
           'discount' => $request->input('discount', 0),
    'tax' => $request->input('tax', 0),
    'subtotal' => 0,
    'total' => 0,
    'paidAmount' => $request->input('paidAmount', 0),
        
    ], $request->except('pilgrims'), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        $busInvoice = BusInvoice::create($data);

        if ($request->has('pilgrims')) {
            $pilgrimsData = [];
            foreach ($request->pilgrims as $pilgrim) {
                $pilgrimsData[$pilgrim['id']] = [
                    'seatNumber' => $pilgrim['seatNumber'],
                    'seatPrice' => $pilgrim['seatPrice'],
                    'status' => $pilgrim['status'] ?? 'booked',
                    'type' => $pilgrim['type'] ?? 'regular',
                    'position' => $pilgrim['position'] ?? null,
                    'creationDate' => now()->format('Y-m-d'),
                    'creationDateHijri' => $this->getHijriDate(),
                ];
                if ($request->has('bus_trip_id')) {
                    $this->updateSeatStatusInTrip($busTrip, $pilgrim['seatNumber'], 'booked');
                }
            }

            $busInvoice->pilgrims()->attach($pilgrimsData);
            $busInvoice->calculateTotal();
        }

        DB::commit();

        return $this->respondWithResource($busInvoice, "تم إنشاء فاتورة الباص بنجاح");

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
    }
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

protected function updateSeatStatusInTrip(BusTrip $busTrip, string $seatNumber, string $status)
{
    $seatMap = $busTrip->seatMap;

    foreach ($seatMap as &$seat) {
        if ($seat['seatNumber'] == $seatNumber) {
            $seat['status'] = $status;
            break;
        }
    }

    $busTrip->seatMap = $seatMap;
    $busTrip->save();
}


    public function edit(string $id)
    {
        $this->authorize('manage_system');

        $busInvoice = BusInvoice::find($id);

        if (!$busInvoice) {
            return response()->json(['message' => "Bus Invoice not found."], 404);
        }

        return $this->respondWithResource($busInvoice, "Bus Invoice retrieved for editing.");
    }

    public function update(BusInvoiceRequest $request, string $id)
{

    $this->authorize('manage_system');

    DB::beginTransaction();
    try {

        $busInvoice = BusInvoice::findOrFail($id);

        $oldData = $busInvoice->toArray();

        $updateData = $request->only([
            'main_pilgrim_id',
            'bus_trip_id',
            'campaign_id',
            'office_id',
            'group_id',
            'worker_id',
            'payment_method_type_id',
            'subtotal',
            'discount',
            'tax',
            'total',
            'paidAmount',
            'invoiceStatus',
            'reason',
            'paymentStatus',
        ]);


        if ($request->has('bus_trip_id') && $request->bus_trip_id != $busInvoice->bus_trip_id) {
            $newBusTrip = BusTrip::findOrFail($request->bus_trip_id);


            if ($request->has('pilgrims')) {
                $this->validateSeatsAvailability($newBusTrip, $request->pilgrims);
            }
        }

        $updateData = array_merge(
            $updateData,
            $this->prepareUpdateMeta($request, $busInvoice->status)
        );


        if ($request->has('pilgrims')) {
            $pilgrimsData = [];
            foreach ($request->pilgrims as $pilgrim) {
                $pilgrimsData[$pilgrim['id']] = [
                    'seatNumber' => $pilgrim['seatNumber'],
                    'seatPrice' => $pilgrim['seatPrice'],
                    'status' => $pilgrim['status'] ?? 'booked',
                    'type' => $pilgrim['type'] ?? 'regular',
                    'position' => $pilgrim['position'] ?? null,
                    'changed_data' => [
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                        'updated_by' => auth()->id(),
                    ]
                ];


                if ($busInvoice->bus_trip_id) {
                    $this->updateSeatStatusInTrip($busInvoice->busTrip, $pilgrim['seatNumber'], 'booked');
                }
            }

            $currentPilgrims = $busInvoice->pilgrims()->withPivot('changed_data')->get()->keyBy('id');

            foreach ($pilgrimsData as $pilgrimId => $newData) {
                if ($currentPilgrims->has($pilgrimId)) {
                    $oldPivotData = $currentPilgrims->get($pilgrimId)->pivot->changed_data ?? [];
                    $pilgrimsData[$pilgrimId]['changed_data'] = array_merge(
                        $oldPivotData,
                        $newData['changed_data']
                    );
                }
            }

            $busInvoice->pilgrims()->sync($pilgrimsData);
        }

        $busInvoice->update($updateData);

        $busInvoice->calculateTotal();

        $changedData = $busInvoice->getChangedData($oldData, $busInvoice->fresh()->toArray());
        $busInvoice->changed_data = $changedData;
        $busInvoice->save();


        DB::commit();

        $this->loadCommonRelations($busInvoice);
        return $this->respondWithResource($busInvoice, "تم تحديث الفاتورة بنجاح");

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Failed to update bus invoice: ' . $e->getMessage());


        return response()->json([
            'message' => 'فشل في تحديث الفاتورة',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function updatePaymentStatus(Request $request, $invoiceId)
    {
        $this->authorize('manage_system');

        $request->validate([
            'paymentStatus' => 'required|in:paid,unpaid,partial',
            'paidAmount' => 'required|numeric|min:0',
        ]);

        $busInvoice = BusInvoice::findOrFail($invoiceId);
        $busInvoice->update([
            'paymentStatus' => $request->paymentStatus,
            'paidAmount' => $request->paidAmount,
        ]);

        return $this->respondWithResource($busInvoice, "Payment status updated successfully.");
    }

    public function getInvoiceStats($invoiceId)
    {
        $this->authorize('manage_system');

        $busInvoice = BusInvoice::findOrFail($invoiceId);

        return response()->json([
            'total_seats' => $busInvoice->pilgrims()->count(),
            'booked_seats' => $busInvoice->pilgrims()->wherePivot('status', 'booked')->count(),
            'cancelled_seats' => $busInvoice->pilgrims()->wherePivot('status', 'cancelled')->count(),
            'subtotal' => $busInvoice->subtotal,
            'discount' => $busInvoice->discount,
            'tax' => $busInvoice->tax,
            'total' => $busInvoice->total,
            'paidAmount' => $busInvoice->paidAmount,
            'remainingAmount' => $busInvoice->total - $busInvoice->paidAmount,
        ]);
    }

        protected function getResourceClass(): string
    {
        return BusInvoiceResource::class;
    }
}
