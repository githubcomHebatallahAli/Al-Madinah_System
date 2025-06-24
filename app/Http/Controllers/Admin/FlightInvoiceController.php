<?php

namespace App\Http\Controllers\Admin;

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

protected function syncPilgrims(FlightInvoice $invoice, array $pilgrims)
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
protected function hasPilgrimsChanges(FlightInvoice $invoice, array $newPilgrims): bool
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

        $query = FlightInvoice::query();

             if ($request->filled('bus_invoice_id')) {
            $query->where('bus_invoice_id', $request->bus_invoice_id);
        }


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

        $FlightInvoices = $query->with(['Flight', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])->orderBy('created_at', 'desc')->paginate(10);
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

        if ($request->filled('bus_invoice_id')) {
            $query->where('bus_invoice_id', $request->bus_invoice_id);
        }


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


        $FlightInvoices = $query->with(['Flight', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])->orderBy('created_at', 'desc')->get();
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
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        $invoice = FlightInvoice::create($data);


        if ($request->has('pilgrims')) {
            $this->attachPilgrims($invoice, $request->pilgrims);
        }

        // if ($request->filled('bus_invoice_id')) {
        //     $this->attachBusPilgrims($invoice, $request->bus_invoice_id);
        // }

        $invoice->PilgrimsCount();
        $invoice->calculateTotal();
        DB::commit();

        return $this->respondWithResource(
            new FlightInvoiceResource($invoice->load(['paymentMethodType.paymentMethod',
            'mainPilgrim',
            'Flight', 'trip','hotel','busInvoice','pilgrims'])),
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
            'Flight', 'trip', 'busInvoice','pilgrims'
    ])->find($id);

        if (!$FlightInvoice) {
            return response()->json(['message' => "Flight Invoice not found."], 404);
        }

        return $this->respondWithResource($FlightInvoice, "Flight Invoice retrieved for editing.");
    }

public function update(FlightInvoiceRequest $request, FlightInvoice $FlightInvoice)
{
    $this->authorize('manage_system');


    if (in_array($FlightInvoice->invoiceStatus, ['approved', 'completed'])) {
        return response()->json([
            'message' => 'لا يمكن تعديل فاتورة معتمدة أو مكتملة'
        ], 422);
    }


    $oldData = $FlightInvoice->toArray();
    $oldPilgrimsData = $FlightInvoice->pilgrims()->get()->mapWithKeys(function ($pilgrim) {
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
            if ($FlightInvoice->$key != $value) {
                $hasChanges = true;
                break;
            }
        }


        $pilgrimsChanged = false;
        $newPilgrimsData = [];
        if ($request->has('pilgrims')) {
            $pilgrimsChanged = $this->hasPilgrimsChanges($FlightInvoice, $request->pilgrims);
            $hasChanges = $hasChanges || $pilgrimsChanged;
        }

        if (!$hasChanges) {
            DB::commit();
            return response()->json([
                'data' => new FlightInvoiceResource($FlightInvoice->load(['Flight', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])),
                'message' => 'لا يوجد تغييرات فعلية'
            ]);
        }

        $FlightInvoice->update($data);


        if ($request->has('pilgrims') && $pilgrimsChanged) {
            $this->syncPilgrims($FlightInvoice, $request->pilgrims);
            $newPilgrimsData = $FlightInvoice->fresh()->pilgrims()->get()->mapWithKeys(function ($pilgrim) {
                return [
                    $pilgrim->id => [
                        'creationDate' => $pilgrim->pivot->creationDate,
                        'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
                    ]
                ];
            })->toArray();
        }

        $FlightInvoice->PilgrimsCount();
        $FlightInvoice->calculateTotal();

        $changedData = $FlightInvoice->getChangedData($oldData, $FlightInvoice->fresh()->toArray());

        if ($pilgrimsChanged) {
            $changedData['pilgrims'] = $this->getPivotChanges($oldPilgrimsData, $newPilgrimsData);
        }

        if (!empty($changedData)) {
            $FlightInvoice->changed_data = $changedData;
            $FlightInvoice->save();
        }

        DB::commit();

        return response()->json([
            'data' => new FlightInvoiceResource($FlightInvoice->load(['Flight', 'trip','hotel', 'busInvoice', 'paymentMethodType', 'pilgrims'])),
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

    $FlightInvoice = FlightInvoice::find($id);
    if (!$FlightInvoice) {
        return response()->json(['message' => "Flight Invoice not found."], 404);
    }

    $oldData = $FlightInvoice->toArray();

    if ($FlightInvoice->invoiceStatus === 'pending') {
        $FlightInvoice->load([ 'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'Flight','hotel' ,'trip', 'busInvoice','pilgrims']);
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
            'Flight', 'trip','hotel' ,'busInvoice','pilgrims']);
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
            'Flight', 'trip','hotel' ,'busInvoice','pilgrims']);
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
            'Flight', 'trip','hotel','busInvoice','pilgrims']);
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
            'Flight','hotel', 'trip', 'busInvoice','pilgrims']);
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
            'Flight', 'trip','hotel' ,'busInvoice','pilgrims']);
    return $this->respondWithResource($FlightInvoice, 'Flight Invoice set to rejected');
}


public function completed($id, Request $request)
{
    $this->authorize('manage_system');

    $validated = $request->validate([
        'payment_method_type_id' => 'required|exists:payment_method_types,id',
        'paidAmount' => 'required|numeric|min:0|max:99999.99',
    ]);

    DB::beginTransaction();

    try {
        $FlightInvoice = FlightInvoice::with([
            'paymentMethodType.paymentMethod',
            'mainPilgrim',
            'Flight', 'trip','hotel', 'busInvoice','pilgrims'
        ])->findOrFail($id);

        if ($FlightInvoice->invoiceStatus === 'completed') {
            $this->loadCommonRelations($FlightInvoice);
            DB::commit();
            return $this->respondWithResource($FlightInvoice, 'فاتورة الطائره مكتملة مسبقاً');
        }

        $originalData = $FlightInvoice->getOriginal();

        // تحديث الحقول الرئيسية
        $FlightInvoice->invoiceStatus = 'completed';
        $FlightInvoice->payment_method_type_id = $validated['payment_method_type_id'];
        $FlightInvoice->paidAmount = $validated['paidAmount'];
        $FlightInvoice->updated_by = $this->getUpdatedByIdOrFail();
        $FlightInvoice->updated_by_type = $this->getUpdatedByType();

        // تسجيل التغييرات الأساسية
        $changedData = [];
        foreach ($FlightInvoice->getDirty() as $field => $newValue) {
            if (array_key_exists($field, $originalData)) {
                $changedData[$field] = [
                    'old' => $originalData[$field],
                    'new' => $newValue
                ];
            }
        }

        // معالجة خاصة لطريقة الدفع
        if ($FlightInvoice->isDirty('payment_method_type_id')) {
            $paymentMethodType = PaymentMethodType::with('paymentMethod')
                ->find($validated['payment_method_type_id']);

            $changedData['payment_method'] = [
                'old' => [
                    'type' => $FlightInvoice->paymentMethodType?->type,
                    'by' => $FlightInvoice->paymentMethodType?->by,
                    'method' => $FlightInvoice->paymentMethodType?->paymentMethod?->name
                ],
                'new' => $paymentMethodType ? [
                    'type' => $paymentMethodType->type,
                    'by' => $paymentMethodType->by,
                    'method' => $paymentMethodType->paymentMethod?->name
                ] : null
            ];
        }

        // تطبيق منطق تتبع التواريخ المطلوب
        if (!empty($changedData)) {
            $previousChanged = $FlightInvoice->changed_data ?? [];

            $newCreationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
            $newCreationDateHijri = $this->getHijriDate();

            $changedData['creationDate'] = [
                'old' => $previousChanged['creationDate']['new'] ?? $FlightInvoice->getOriginal('creationDate'),
                'new' => $newCreationDate
            ];

            $changedData['creationDateHijri'] = [
                'old' => $previousChanged['creationDateHijri']['new'] ?? $FlightInvoice->getOriginal('creationDateHijri'),
                'new' => $newCreationDateHijri
            ];

        }

        $FlightInvoice->changed_data = $changedData;
        $FlightInvoice->save();

        // تحديث البيانات المحسوبة
        $FlightInvoice->PilgrimsCount();
        $FlightInvoice->calculateTotal();

        $this->loadCommonRelations($FlightInvoice);
        DB::commit();

        return $this->respondWithResource(
            $FlightInvoice,
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


// protected function attachBusPilgrims(FlightInvoice $invoice, $FlightInvoiceId)
// {
//     if (empty($FlightInvoiceId)) {
//         return;
//     }

//     $FlightInvoice = BusInvoice::with('pilgrims')->find($FlightInvoiceId);

//     if (!$FlightInvoice) {
//         throw new \Exception('عفواً، فاتورة الباص المحددة غير موجودة!');
//     }

//     $hijriDate = $this->getHijriDate();
//     $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

//     $pilgrimsData = $FlightInvoice->pilgrims->mapWithKeys(function ($pilgrim) use ($currentDate, $hijriDate) {
//         return [
//             $pilgrim->id => [
//                 'creationDate' => $currentDate,
//                 'creationDateHijri' => $hijriDate,
//                 'changed_data' => null
//             ]
//         ];
//     });

//     $invoice->pilgrims()->attach($pilgrimsData->toArray());
// }



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
