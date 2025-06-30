<?php

namespace App\Http\Controllers\Admin;

use App\Models\Flight;
use App\Models\Pilgrim;
use Illuminate\Http\Request;
use App\Models\FlightInvoice;
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
use App\Http\Requests\Admin\FlightInvoiceRequest;
use App\Http\Resources\Admin\FlightInvoiceResource;
use App\Http\Resources\Admin\ShowAllFlightInvoiceResource;

class FlightInvoiceController extends Controller
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

protected function attachPilgrims(FlightInvoice $invoice, array $pilgrims)
{
    $flight = $invoice->flight()->lockForUpdate()->first(); // 🔒 تأمين المقاعد أثناء الحجز
    $availableSeats = $flight->seatNum ?? [];
    $remainingQuantity = $flight->quantity;

    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);

        $seatsRequested = $pilgrim['seatNumber'] ?? [];

        if (!is_array($seatsRequested) || count($seatsRequested) == 0) {
            throw new \Exception("يجب تحديد المقاعد المخصصة للحاج {$p->name} كمصفوفة.");
        }

        foreach ($seatsRequested as $seat) {
            if (!in_array($seat, $availableSeats)) {
                throw new \Exception("المقعد $seat غير متاح حالياً.");
            }
        }

        if (count($seatsRequested) > count($availableSeats)) {
            throw new \Exception("عدد المقاعد المطلوبة للحاج {$p->name} غير متاح.");
        }

        $availableSeats = array_values(array_diff($availableSeats, $seatsRequested));
        $remainingQuantity -= count($seatsRequested);

        $pilgrimsData[$p->id] = [
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null,
            'seatNumber' => implode(',', $seatsRequested),
        ];
    }
    $flight->seatNum = array_values($availableSeats); // إعادة ترتيب المقاعد
    $flight->quantity = $remainingQuantity;
    $flight->save();

    $invoice->pilgrims()->attach($pilgrimsData);
}



        public function showAllWithPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = FlightInvoice::query();


        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

                if ($request->filled('flight_id')) {
            $query->where('flight_id', $request->flight_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $FlightInvoices = $query->with(['flight', 'trip', 'paymentMethodType', 'pilgrims'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = FlightInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllFlightInvoiceResource::collection($FlightInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'pagination' => [
                'total' => $FlightInvoices->total(),
                'count' => $FlightInvoices->count(),
                'per_page' => $FlightInvoices->perPage(),
                'current_page' => $FlightInvoices->currentPage(),
                'total_pages' => $FlightInvoices->lastPage(),
                'next_page_url' => $FlightInvoices->nextPageUrl(),
                'prev_page_url' => $FlightInvoices->previousPageUrl(),
            ],
            'message' => "Show All Flight Invoices."
        ]);
    }

    public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = FlightInvoice::query();

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('flight_id')) {
            $query->where('flight_id', $request->flight_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }
        $FlightInvoices = $query->with(['flight', 'trip', 'paymentMethodType', 'pilgrims'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = FlightInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllFlightInvoiceResource::collection($FlightInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'message' => "Show All Flight Invoices."
        ]);
    }

public function create(FlightInvoiceRequest $request)
{
    $this->authorize('manage_system');
    $data = array_merge([
        'discount' => $this->ensureNumeric($request->input('discount', 0)),
        'tax' => $this->ensureNumeric($request->input('tax', 0)),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
        'subtotal' => 0,
        'total' => 0,
        'totalAfterDiscount'=>0,
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        $invoice = FlightInvoice::create($data);
        if ($request->has('pilgrims')) {
            $this->attachPilgrims($invoice, $request->pilgrims);
        }
        $invoice->PilgrimsCount();
        $invoice->calculateTotal();
        DB::commit();

        return $this->respondWithResource(
            new FlightInvoiceResource($invoice->load(['paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','hotel','pilgrims'])),
            'تم إنشاء فاتورة الطيران بنجاح'
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

        $FlightInvoice =FlightInvoice::with([
          'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','pilgrims'
    ])->find($id);

        if (!$FlightInvoice) {
            return response()->json(['message' => "Flight Invoice not found."], 404);
        }

        return $this->respondWithResource($FlightInvoice, "Flight Invoice retrieved for editing.");
    }

protected function hasPilgrimsChanges(FlightInvoice $invoice, array $newPilgrims): bool
{
    $currentPilgrims = $invoice->pilgrims()->get()->keyBy('id');
    if (count($currentPilgrims) !== count($newPilgrims)) {
        return true;
    }

    foreach ($newPilgrims as $newPilgrim) {
        if (empty($newPilgrim['idNum'])) {
            return true;
        }

        $pilgrimId = $newPilgrim['id'] ?? null;
        if (!$pilgrimId || !$currentPilgrims->has($pilgrimId)) {
            return true;
        }

        $currentPilgrim = $currentPilgrims->get($pilgrimId);
        if ($currentPilgrim->name !== $newPilgrim['name'] ||
            $currentPilgrim->nationality !== $newPilgrim['nationality'] ||
            $currentPilgrim->gender !== $newPilgrim['gender'] ||
            $currentPilgrim->phoNum !== ($newPilgrim['phoNum'] ?? null)) {
            return true;
        }


        $currentSeats = explode(',', $currentPilgrim->pivot->seatNumber);
        $newSeats = $newPilgrim['seatNumber'] ?? [];

        if (array_diff($currentSeats, $newSeats) || array_diff($newSeats, $currentSeats)) {
            return true;
        }
    }

    return false;
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

    $FlightInvoice = FlightInvoice::find($id);
    if (!$FlightInvoice) {
        return response()->json(['message' => "Flight Invoice not found."], 404);
    }

    $oldData = $FlightInvoice->toArray();

    if ($FlightInvoice->invoiceStatus === 'pending') {
        $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight','hotel' ,'trip','pilgrims']);
        return $this->respondWithResource($FlightInvoice, 'Flight Invoice is already set to pending');
    }

    $FlightInvoice->invoiceStatus = 'pending';
    $FlightInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $FlightInvoice->creationDateHijri = $this->getHijriDate();
    $FlightInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $FlightInvoice->updated_by_type = $this->getUpdatedByType();
    $FlightInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $FlightInvoice->creationDate,
        'creationDateHijri' => $FlightInvoice->creationDateHijri,
    ];

    $changedData = $FlightInvoice->getChangedData($oldData, array_merge($FlightInvoice->fresh()->toArray(), $metaForDiffOnly));
    $FlightInvoice->changed_data = $changedData;
    $FlightInvoice->save();

    $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','hotel' ,'pilgrims']);
    return $this->respondWithResource($FlightInvoice, 'Flight Invoice set to pending');
}

public function approved($id)
{
    $this->authorize('manage_system');

    $FlightInvoice = FlightInvoice::find($id);
    if (!$FlightInvoice) {
        return response()->json(['message' => "Flight Invoice not found."], 404);
    }

    $oldData = $FlightInvoice->toArray();

    if ($FlightInvoice->invoiceStatus === 'approved') {
        $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','hotel','pilgrims']);
        return $this->respondWithResource($FlightInvoice, 'Flight Invoice is already set to approved');
    }

    $FlightInvoice->invoiceStatus = 'approved';
    $FlightInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $FlightInvoice->creationDateHijri = $this->getHijriDate();
    $FlightInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $FlightInvoice->updated_by_type = $this->getUpdatedByType();
    $FlightInvoice->save();

    //  $FlightInvoice->PilgrimsCount();
    // $FlightInvoice->calculateTotal();
    $metaForDiffOnly = [
        'creationDate' => $FlightInvoice->creationDate,
        'creationDateHijri' => $FlightInvoice->creationDateHijri,
    ];
    $changedData = $FlightInvoice->getChangedData($oldData, array_merge($FlightInvoice->fresh()->toArray(), $metaForDiffOnly));
    $FlightInvoice->changed_data = $changedData;
    $FlightInvoice->save();

    $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','hotel','pilgrims']);
    return $this->respondWithResource($FlightInvoice, 'Flight Invoice set to approved');
}

public function rejected(string $id, Request $request)
{
    $this->authorize('manage_system');
     $validated = $request->validate([
        'reason' => 'nullable|string',
    ]);

    $FlightInvoice = FlightInvoice::find($id);
    if (!$FlightInvoice) {
        return response()->json(['message' => "Flight Invoice not found."], 404);
    }

    $oldData = $FlightInvoice->toArray();

    if ($FlightInvoice->invoiceStatus === 'rejected') {
        $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight','hotel', 'trip','pilgrims']);
        return $this->respondWithResource($FlightInvoice, 'Flight Invoice is already set to rejected');
    }

    $FlightInvoice->invoiceStatus = 'rejected';
    $FlightInvoice->reason = $validated['reason'] ?? null;
    // $FlightInvoice->subtotal = 0;
    // $FlightInvoice->total = 0;
    // $FlightInvoice->paidAmount = 0;
    $FlightInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $FlightInvoice->creationDateHijri = $this->getHijriDate();
    $FlightInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $FlightInvoice->updated_by_type = $this->getUpdatedByType();
    $FlightInvoice->save();

    //  $FlightInvoice->PilgrimsCount();
    // $FlightInvoice->calculateTotal();

    $metaForDiffOnly = [
        'creationDate' => $FlightInvoice->creationDate,
        'creationDateHijri' => $FlightInvoice->creationDateHijri,
    ];

    $changedData = $FlightInvoice->getChangedData($oldData, array_merge($FlightInvoice->fresh()->toArray(), $metaForDiffOnly));
    $FlightInvoice->changed_data = $changedData;
    $FlightInvoice->save();

    $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','hotel' ,'pilgrims']);
    return $this->respondWithResource($FlightInvoice, 'Flight Invoice set to rejected');
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
        $hotelInvoice = FlightInvoice::with([
             'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'flight', 'trip','hotel','pilgrims'
        ])->findOrFail($id);

        if ($hotelInvoice->invoiceStatus === 'completed') {
            $this->loadCommonRelations($hotelInvoice);
            DB::commit();
            return $this->respondWithResource($hotelInvoice, 'فاتورة الطيران مكتملة مسبقاً');
        }

        $originalData = $hotelInvoice->getOriginal();

        $hotelInvoice->invoiceStatus = 'completed';
        $hotelInvoice->payment_method_type_id = $validated['payment_method_type_id'];
        $hotelInvoice->paidAmount = $validated['paidAmount'];
        $hotelInvoice->discount = $validated['discount'] ?? 0;
        $hotelInvoice->tax = $validated['tax'] ?? 0;
        $hotelInvoice->updated_by = $this->getUpdatedByIdOrFail();
        $hotelInvoice->updated_by_type = $this->getUpdatedByType();


        $changedData = [];
        foreach ($hotelInvoice->getDirty() as $field => $newValue) {
            if (array_key_exists($field, $originalData)) {
                $changedData[$field] = [
                    'old' => $originalData[$field],
                    'new' => $newValue
                ];
            }
        }

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
            'تم إكمال فاتورة الطائره بنجاح'
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

    $FlightInvoice = FlightInvoice::find($id);
    if (!$FlightInvoice) {
        return response()->json(['message' => "Flight Invoice not found."], 404);
    }

    $oldData = $FlightInvoice->toArray();

    if ($FlightInvoice->invoiceStatus === 'absence') {
        $this->loadCommonRelations($FlightInvoice);
        return $this->respondWithResource($FlightInvoice, 'Flight Invoice is already set to absence');
    }

    $FlightInvoice->invoiceStatus = 'absence';
    $FlightInvoice->reason = $validated['reason'] ?? null;
    $FlightInvoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $FlightInvoice->creationDateHijri = $this->getHijriDate();
    $FlightInvoice->updated_by = $this->getUpdatedByIdOrFail();
    $FlightInvoice->updated_by_type = $this->getUpdatedByType();
    $FlightInvoice->save();

    $metaForDiffOnly = [
        'creationDate' => $FlightInvoice->creationDate,
        'creationDateHijri' => $FlightInvoice->creationDateHijri,
    ];

    $changedData = $FlightInvoice->getChangedData($oldData, array_merge($FlightInvoice->fresh()->toArray(), $metaForDiffOnly));
    $FlightInvoice->changed_data = $changedData;
    $FlightInvoice->save();
    return $this->respondWithResource($FlightInvoice, 'Flight Invoice set to absence');
}


        protected function getResourceClass(): string
    {
        return FlightInvoiceResource::class;
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

public function update(FlightInvoiceRequest $request, FlightInvoice $FlightInvoice)
{
    $this->authorize('manage_system');

    DB::beginTransaction();
    try {
        $FlightInvoice->load([
            'flight' => function($q) { $q->lockForUpdate(); },
            'pilgrims'
        ]);

        $actualFlightId = DB::table('flight_invoices')
            ->where('id', $FlightInvoice->id)
            ->value('flight_id');

        $flightExists = $actualFlightId && Flight::where('id', $actualFlightId)
            ->lockForUpdate()
            ->exists();

        if (!$flightExists) {
            DB::rollBack();
            return response()->json([
                'message' => 'الرحلة غير موجودة في قاعدة البيانات',
                'flight_id' => $FlightInvoice->flight_id,
            ], 422);
        }

        if (in_array($FlightInvoice->invoiceStatus, ['approved', 'completed'])) {
            DB::rollBack();
            return response()->json([
                'message' => 'لا يمكن تعديل فاتورة معتمدة أو مكتملة'
            ], 422);
        }

        $oldData = $FlightInvoice->toArray();
        $oldPilgrimsData = $FlightInvoice->pilgrims()->withPivot('seatNumber')->get()
            ->mapWithKeys(function ($pilgrim) {
                return [
                    $pilgrim->id => [
                        'seatNumber' => $pilgrim->pivot->seatNumber,
                        'creationDate' => $pilgrim->pivot->creationDate,
                        'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
                    ]
                ];
            })->toArray();

        $data = array_merge([
            'discount' => $this->ensureNumeric($request->input('discount', 0)),
            'tax' => $this->ensureNumeric($request->input('tax', 0)),
            'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
        ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareUpdateMetaData());

        $hasChanges = false;
        foreach ($data as $key => $value) {
            if ($FlightInvoice->$key != $value) {
                $hasChanges = true;
                break;
            }
        }

        $pilgrimsChanged = false;
        if ($request->has('pilgrims')) {
            $pilgrimsChanged = $this->hasPilgrimsChanges($FlightInvoice, $request->pilgrims);
            $hasChanges = $hasChanges || $pilgrimsChanged;
        }

        if (!$hasChanges) {
            DB::commit();
            return response()->json([
                'data' => new FlightInvoiceResource(
                    $FlightInvoice->load(['flight', 'trip', 'hotel', 'paymentMethodType', 'pilgrims'])
                ),
                'message' => 'لا يوجد تغييرات فعلية'
            ]);
        }

        $FlightInvoice->update($data);

        if ($pilgrimsChanged && $request->has('pilgrims')) {
            $this->syncPilgrims($FlightInvoice, $request->pilgrims);
        }

        $FlightInvoice->PilgrimsCount();
        $FlightInvoice->calculateTotal();

        $changedData = [];
        $newData = $FlightInvoice->fresh()->toArray();

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $oldData)) {
                $oldValue = $oldData[$key];
                $newValue = $newData[$key] ?? $value;

                if ($oldValue != $newValue) {
                    $changedData[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
        }

        if ($pilgrimsChanged) {
            $newPilgrimsData = $FlightInvoice->pilgrims()->withPivot('seatNumber')->get()
                ->mapWithKeys(function ($pilgrim) {
                    return [
                        $pilgrim->id => [
                            'seatNumber' => $pilgrim->pivot->seatNumber,
                            'creationDate' => $pilgrim->pivot->creationDate,
                            'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
                        ]
                    ];
                })->toArray();

            $pilgrimChanges = [];

            foreach ($oldPilgrimsData as $pilgrimId => $oldPivot) {
                if (!isset($newPilgrimsData[$pilgrimId])) {
                    $pilgrimChanges[$pilgrimId] = [
                        'old' => $oldPivot,
                        'new' => null
                    ];
                    continue;
                }

                $newPivot = $newPilgrimsData[$pilgrimId];
                $changes = [];

                foreach ($oldPivot as $key => $oldValue) {
                    if ($newPivot[$key] != $oldValue) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newPivot[$key]
                        ];
                    }
                }

                if (!empty($changes)) {
                    $pilgrimChanges[$pilgrimId] = $changes;
                }
            }

            foreach ($newPilgrimsData as $pilgrimId => $newPivot) {
                if (!isset($oldPilgrimsData[$pilgrimId])) {
                    $pilgrimChanges[$pilgrimId] = [
                        'old' => null,
                        'new' => $newPivot
                    ];
                }
            }

            if (!empty($pilgrimChanges)) {
                $changedData['pilgrims'] = $pilgrimChanges;
            }
        }

        if ($FlightInvoice->isDirty('flight_id')) {
            $oldFlight = Flight::find($oldData['flight_id']);
            $newFlight = $FlightInvoice->flight;

            $changedData['flight'] = [
                'old' => $oldFlight ? [
                    'id' => $oldFlight->id,
                    'direction' => $oldFlight->direction,
                    'quantity' => $oldFlight->quantity,
                    'seatNum' => $oldFlight->seatNum,
                    'class' => $oldFlight->class
                ] : null,
                'new' => $newFlight ? [
                    'id' => $newFlight->id,
                    'direction' => $newFlight->direction,
                    'quantity' => $newFlight->quantity,
                    'seatNum' => $newFlight->seatNum,
                    'class' => $newFlight->class
                ] : null
            ];
        }

        $changedData['creationDate'] = [
            'old' => $oldData['creationDate'],
            'new' => $newData['creationDate']
        ];

        $changedData['creationDateHijri'] = [
            'old' => $oldData['creationDateHijri'],
            'new' => $newData['creationDateHijri']
        ];

        if (!empty($changedData)) {
            $FlightInvoice->changed_data = $changedData;
            $FlightInvoice->save();
        }

        DB::commit();

        return response()->json([
            'data' => new FlightInvoiceResource($FlightInvoice->fresh([
                'flight', 'trip', 'hotel', 'paymentMethodType', 'pilgrims'
            ])),
            'message' => 'تم تحديث الفاتورة بنجاح'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('فشل تحديث الفاتورة', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'invoice_id' => $FlightInvoice->id,
            'request_data' => $request->all()
        ]);

        return response()->json([
            'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}

protected function syncPilgrims(FlightInvoice $invoice, array $pilgrims)
{
    $invoice->load(['flight' => function($query) {
        $query->lockForUpdate();
    }]);

    if (!$invoice->flight) {
        throw new \Exception('الرحلة المحددة غير موجودة في النظام. رقم الرحلة: ' . $invoice->flight_id);
    }

    $flight = $invoice->flight;
    $availableSeats = $flight->seatNum ?? [];
    $remainingQuantity = $flight->quantity ?? 0;

    $oldPilgrims = $invoice->pilgrims()->withPivot('seatNumber')->get();
    foreach ($oldPilgrims as $oldPilgrim) {
        $oldSeats = explode(',', $oldPilgrim->pivot->seatNumber);
        $availableSeats = array_merge($availableSeats, $oldSeats);
        $remainingQuantity += count($oldSeats);
    }
    $availableSeats = array_values(array_unique($availableSeats));

    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);
        $seatsRequested = $pilgrim['seatNumber'] ?? [];

        if (!is_array($seatsRequested) || empty($seatsRequested)) {
            throw new \Exception("يجب تحديد المقاعد المخصصة للحاج {$p->name} كمصفوفة غير فارغة.");
        }

        foreach ($seatsRequested as $seat) {
            if (!in_array($seat, $availableSeats)) {
                throw new \Exception("المقعد $seat غير متاح حالياً.");
            }
        }

        if (count($seatsRequested) > count($availableSeats)) {
            throw new \Exception("عدد المقاعد المطلوبة للحاج {$p->name} غير متاح.");
        }

        $availableSeats = array_values(array_diff($availableSeats, $seatsRequested));
        $remainingQuantity -= count($seatsRequested);

        $existingPivot = $oldPilgrims->firstWhere('id', $p->id);
        $pilgrimsData[$p->id] = [
            'creationDate' => $existingPivot?->pivot?->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot?->pivot?->creationDateHijri ?? $hijriDate,
            'changed_data' => null,
            'seatNumber' => implode(',', $seatsRequested),
        ];
    }

    $flight->seatNum = array_values($availableSeats);
    $flight->quantity = $remainingQuantity;
    $flight->save();

    $invoice->pilgrims()->sync($pilgrimsData);

    $invoice->PilgrimsCount();
    $invoice->calculateTotal();
}

}
