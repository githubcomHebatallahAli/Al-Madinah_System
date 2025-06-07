<?php

namespace App\Http\Controllers\Admin;

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



public function store(BusInvoiceRequest $request)
{
    DB::beginTransaction();

    try {
        $validated = $request->validated();

        $creationData = $this->prepareCreationMetaData();
        $validated = array_merge($validated, $creationData);

        $invoice = BusInvoice::create($validated);

        if ($request->has('pilgrims') && is_array($request->pilgrims)) {
            $this->validateAndAttachPilgrims($invoice, $request->pilgrims);
        }


        $invoice->calculateTotal();
        $invoice->updateSeatsCount();

        DB::commit();

        return $this->respondWithResource($invoice->load([
            'bus', 'trip', 'campaign', 'office', 'group',
            'busDriver', 'worker', 'paymentMethodType', 'pilgrims'
        ]), 'Bus invoice created successfully with pilgrims');

    } catch (\Exception $e) {
        DB::rollBack();
        return $this->handleError($e, 'Failed to create bus invoice with pilgrims');
    }
}


protected function validateAndAttachPilgrims(BusInvoice $invoice, array $pilgrimsData): void
{
    $bus = $invoice->bus;
    $availableSeats = $bus->available_seats;
    $availableSeatNumbers = array_column($availableSeats, 'seatNumber');

    $pilgrimsToAttach = [];
    $seatNumbersUsed = [];

    foreach ($pilgrimsData as $pilgrim) {
        if (!in_array($pilgrim['seatNumber'], $availableSeatNumbers)) {
            throw new \Exception("Seat {$pilgrim['seatNumber']} is not available or does not exist");
        }

        if (in_array($pilgrim['seatNumber'], $seatNumbersUsed)) {
            throw new \Exception("Seat {$pilgrim['seatNumber']} is assigned to multiple pilgrims");
        }

        $seatNumbersUsed[] = $pilgrim['seatNumber'];

        $pilgrimsToAttach[$pilgrim['id']] = [
            'seatNumber' => $pilgrim['seatNumber'],
            'seatPrice' => $pilgrim['seatPrice'],
            'status' => 'booked',
            'creationDate' => now(),
            'creationDateHijri' => $this->getHijriDate()
        ];
    }

    // Check if bus capacity is not exceeded
    if (count($pilgrimsToAttach) > ($bus->seatNum - $bus->total_bookedSeats)) {
        throw new \Exception("Bus capacity exceeded. Available seats: " . ($bus->seatNum - $bus->total_bookedSeats));
    }

    $invoice->pilgrims()->attach($pilgrimsToAttach);
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
