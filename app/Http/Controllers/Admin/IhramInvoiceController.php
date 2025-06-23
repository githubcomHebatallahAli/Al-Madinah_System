<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pilgrim;
use App\Models\IhramSupply;
use App\Models\IhramInvoice;
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
use App\Http\Requests\Admin\IhramInvoiceRequest;
use App\Http\Resources\Admin\IhramInvoiceResource;
use App\Http\Resources\Admin\ShowAllIhramInvoiceResource;

class IhramInvoiceController extends Controller
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

        $query = IhramInvoice::query();

             if ($request->filled('bus_invoice_id')) {
            $query->where('bus_invoice_id', $request->bus_invoice_id);
        }


        if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $ihramInvoices = $query->with(['busInvoice', 'paymentMethodType', 'pilgrims', 'ihramSupplies'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = IhramInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllIhramInvoiceResource::collection($ihramInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,
        ],
            'pagination' => [
                'total' => $ihramInvoices->total(),
                'count' => $ihramInvoices->count(),
                'per_page' => $ihramInvoices->perPage(),
                'current_page' => $ihramInvoices->currentPage(),
                'total_pages' => $ihramInvoices->lastPage(),
                'next_page_url' => $ihramInvoices->nextPageUrl(),
                'prev_page_url' => $ihramInvoices->previousPageUrl(),
            ],
            'message' => "Show All Ihram Invoices."
        ]);
    }

    public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = IhramInvoice::query();

        if ($request->filled('bus_invoice_id')) {
            $query->where('bus_invoice_id', $request->bus_invoice_id);
        }


          if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
        }

        if ($request->filled('invoiceStatus')) {
            $query->where('invoiceStatus', $request->invoiceStatus);
        }

        $ihramInvoices = $query->with(['busInvoice', 'paymentMethodType', 'pilgrims', 'ihramSupplies'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = IhramInvoice::sum('paidAmount');

        return response()->json([
            'data' => ShowAllIhramInvoiceResource::collection($ihramInvoices),
             'statistics' => [
            'paid_amount' => $totalPaidAmount,

        ],
            'message' => "Show All Ihram Invoices."
        ]);
    }

public function create(IhramInvoiceRequest $request)
{
    $this->authorize('manage_system');

    $data = array_merge([
        'discount'   => $this->ensureNumeric($request->input('discount', 0)),
        'tax'        => $this->ensureNumeric($request->input('tax', 0)),
        'paidAmount' => $this->ensureNumeric($request->input('paidAmount', 0)),
        'subtotal'   => 0,
        'total'      => 0,
    ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims', 'ihramSupplies']), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {

        $invoice = IhramInvoice::create($data);

        $totalPrice = 0;
        $outOfStockSupplies = [];


        if ($request->has('ihramSupplies')) {
            foreach ($request->ihramSupplies as $supply) {
                $supplyModel = IhramSupply::find($supply['id']);

                if ($supplyModel->quantity <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "المستلزم '{$supplyModel->ihramItem->name}' غير متوفر في المخزون",
                    ], 400);
                }

                if ($supply['quantity'] > $supplyModel->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "الكمية المطلوبة لـ'{$supplyModel->ihramItem->name}' غير متوفرة. المتاح: {$supplyModel->quantity}",
                    ], 400);
                }


                $supplyModel->decrement('quantity', $supply['quantity']);

                if ($supplyModel->quantity === 0) {
                    $outOfStockSupplies[] = $supplyModel->ihramItem->name;
                }


                $totalPriceForSupply = $supplyModel->sellingPrice * $supply['quantity'];
                $totalPrice += $totalPriceForSupply;

                $invoice->ihramSupplies()->attach($supply['id'], [
                    'quantity'         => $supply['quantity'],
                    'price'            => $supplyModel->sellingPrice,
                    'total'            => $totalPriceForSupply,
                    'creationDate'     => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    'creationDateHijri'=> $this->getHijriDate(),
                    'changed_data'     => null
                ]);
            }
        }


        if ($request->filled('bus_invoice_id')) {
            $this->attachBusPilgrims($invoice, $request->bus_invoice_id);
        }

        elseif ($request->has('pilgrims')) {
            $this->attachPilgrims($invoice, $request->pilgrims);
        }


        $subtotal = $totalPrice;
        $discount = $invoice->discount;
        $tax = $invoice->tax;
        $total = $subtotal - $discount + $tax;


        $invoice->update([
            'subtotal'      => $subtotal,
            'total'         => $total,

        ]);

        $invoice->updateIhramSuppliesCount();

        DB::commit();

        $response = [
            'data'      => new IhramInvoiceResource($invoice->load(['busInvoice', 'paymentMethodType', 'pilgrims', 'ihramSupplies'])),
            'message'   => 'تم إنشاء فاتورة مستلزمات الإحرام بنجاح',
            'subtotal'  => $subtotal,
            'discount'  => $discount,
            'tax'       => $tax,
            'total'     => $total,
            'paidAmount'=> $invoice->paidAmount,
        ];

        if (!empty($outOfStockSupplies)) {
            $response['warning'] = "المستلزمات التالية نفدت من المخزون: " . implode(', ', $outOfStockSupplies);
        }

        return response()->json($response);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}

        public function edit(string $id)
    {
        $this->authorize('manage_system');

        $ihramInvoice =IhramInvoice::with([
          'busInvoice', 'paymentMethodType', 'pilgrims', 'ihramSupplies'
    ])->find($id);

        if (!$ihramInvoice) {
            return response()->json(['message' => "Ihram Supplies Invoice not found."], 404);
        }

        return $this->respondWithResource($ihramInvoice, "Ihram Supplies Invoice retrieved for editing.");
    }

public function update(IhramInvoiceRequest $request, IhramInvoice $ihramInvoice)
{
    $this->authorize('manage_system');


    if (in_array($ihramInvoice->invoiceStatus, ['approved', 'completed'])) {
        return response()->json([
            'message' => 'لا يمكن تعديل فاتورة معتمدة أو مكتملة'
        ], 422);
    }

    $oldData = $ihramInvoice->toArray();


    $oldPivotSupplies = $ihramInvoice->ihramSupplies->mapWithKeys(function ($supply) {
        return [
            $supply->id => [
                'quantity' => $supply->pivot->quantity,
                'price' => $supply->pivot->price,
                'total' => $supply->pivot->total,
            ],
        ];
    })->toArray();


    $oldPivotPilgrims = $ihramInvoice->pilgrims->mapWithKeys(function ($pilgrim) {
        return [
            $pilgrim->id => [
                'creationDate' => $pilgrim->pivot->creationDate,
                'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
            ],
        ];
    })->toArray();

    DB::beginTransaction();
    try {
        $data = array_merge([
            'discount'   => $this->ensureNumeric($request->input('discount')),
            'tax'        => $this->ensureNumeric($request->input('tax')),
            'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
        ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims', 'ihramSupplies']), $this->prepareUpdateMetaData());

        $totalPrice = 0;
        $outOfStockSupplies = [];
        $suppliesData = [];
        $errors = [];


        if ($request->has('ihramSupplies')) {
            foreach ($request->ihramSupplies as $supply) {
                $supplyModel = IhramSupply::find($supply['id']);
                $previousQuantity = $oldPivotSupplies[$supply['id']]['quantity'] ?? 0;
                $newQuantity = $supply['quantity'];

                if ($newQuantity > $previousQuantity) {
                    $diff = $newQuantity - $previousQuantity;
                    if ($diff > $supplyModel->quantity) {
                        $errors[] = "الكمية غير كافية لـ'{$supplyModel->ihramItem->name}'. المتاح: {$supplyModel->quantity}";
                        continue;
                    }
                    $supplyModel->decrement('quantity', $diff);
                } elseif ($newQuantity < $previousQuantity) {
                    $supplyModel->increment('quantity', $previousQuantity - $newQuantity);
                }

                $totalPriceForSupply = $supplyModel->sellingPrice * $newQuantity;
                $totalPrice += $totalPriceForSupply;

                $suppliesData[$supply['id']] = [
                    'quantity' => $newQuantity,
                    'price' => $supplyModel->sellingPrice,
                    'total' => $totalPriceForSupply,
                    'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    'creationDateHijri' => $this->getHijriDate(),
                    'changed_data' => null
                ];
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'حدثت أخطاء أثناء تحديث الفاتورة',
                    'errors'  => $errors,
                ], 400);
            }


            $supplyPivotChanges = $this->getPivotChanges($oldPivotSupplies, $suppliesData);
            foreach ($supplyPivotChanges as $supplyId => $change) {
                if (isset($suppliesData[$supplyId])) {
                    $suppliesData[$supplyId]['changed_data'] = json_encode($change, JSON_UNESCAPED_UNICODE);
                }
            }

            $ihramInvoice->ihramSupplies()->sync($suppliesData);
        } else {
            $totalPrice = $ihramInvoice->ihramSupplies->sum(function($supply) {
                return $supply->pivot->quantity * $supply->pivot->price;
            });
        }


        if ($request->filled('bus_invoice_id')) {
            $this->attachBusPilgrims($ihramInvoice, $request->bus_invoice_id);
        } elseif ($request->has('pilgrims')) {
            $pilgrimsChanged = $this->hasPilgrimsChanges($ihramInvoice, $request->pilgrims);

            if ($pilgrimsChanged) {

                $this->syncPilgrims($ihramInvoice, $request->pilgrims);
            }
        }


        $hasChanges = false;
        foreach ($data as $key => $value) {
            if ($ihramInvoice->$key != $value) {
                $hasChanges = true;
                break;
            }
        }


        if ($hasChanges || $request->has('ihramSupplies') || ($request->has('pilgrims') && isset($pilgrimsChanged) && $pilgrimsChanged)) {
            $ihramInvoice->update($data);

            $subtotal = $totalPrice;
            $discount = $ihramInvoice->discount;
            $tax = $ihramInvoice->tax;
            $total = $subtotal - $discount + $tax;

            $ihramInvoice->update([
                'subtotal' => $subtotal,
                'total' => $total
            ]);

            $ihramInvoice->updateIhramSuppliesCount();


            $changedData = $ihramInvoice->getChangedData($oldData, $ihramInvoice->fresh()->toArray());
            $ihramInvoice->changed_data = $changedData;
            $ihramInvoice->save();
        }

        DB::commit();

        $response = [
            'data'       => new IhramInvoiceResource($ihramInvoice->load(['busInvoice', 'paymentMethodType', 'pilgrims', 'ihramSupplies'])),
            'message'    => 'تم تحديث فاتورة مستلزمات الإحرام بنجاح',
            'subtotal'   => $subtotal ?? $ihramInvoice->subtotal,
            'discount'   => $discount ?? $ihramInvoice->discount,
            'tax'        => $tax ?? $ihramInvoice->tax,
            'total'      => $total ?? $ihramInvoice->total,
            'paidAmount' => $ihramInvoice->paidAmount,
        ];

        if (!empty($outOfStockSupplies)) {
            $response['warning'] = "المستلزمات التالية نفدت من المخزون: " . implode(', ', $outOfStockSupplies);
        }

        return response()->json($response);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
        ], 500);
    }
}






        protected function findOrCreatePilgrimForInvoice(array $pilgrimData): Pilgrim
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

    // تحديث بيانات الحاج الموجود
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

protected function attachPilgrims(IhramInvoice $invoice, array $pilgrims)
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

protected function syncPilgrims(IhramInvoice $invoice, array $pilgrims)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    // 🟠 حفظ بيانات البفوت القديمة قبل التعديل
    $oldPivotPilgrims = $invoice->pilgrims->mapWithKeys(function ($pilgrim) {
        return [
            $pilgrim->id => [
                'creationDate' => $pilgrim->pivot->creationDate,
                'creationDateHijri' => $pilgrim->pivot->creationDateHijri,
            ],
        ];
    })->toArray();

    foreach ($pilgrims as $pilgrim) {
        $p = $this->findOrCreatePilgrimForInvoice($pilgrim);
        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $p->id)->first();

        $pilgrimsData[$p->id] = [
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null, // هيتحدث لاحقًا
        ];
    }

    // 🟢 تنفيذ منطق التغيير وتسجيل الفرق
    $pivotChanges = $this->getPivotChanges($oldPivotPilgrims, $pilgrimsData);

    foreach ($pivotChanges as $pilgrimId => $change) {
        if (isset($pilgrimsData[$pilgrimId])) {
            $pilgrimsData[$pilgrimId]['changed_data'] = json_encode($change, JSON_UNESCAPED_UNICODE);
        }
    }

    $invoice->pilgrims()->sync($pilgrimsData);
}

protected function hasPilgrimsChanges(IhramInvoice $invoice, array $newPilgrims): bool
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


    protected function ensureNumeric($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    return is_numeric($value) ? $value : 0;
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

    $invoice = IhramInvoice::find($id);
    if (!$invoice) {
        return response()->json(['message' => "Invoice not found."], 404);
    }

    $oldData = $invoice->toArray();

    if ($invoice->invoiceStatus === 'pending') {
        $invoice->load(['busInvoice', 'pilgrims', 'ihramSupplies' ,'paymentMethodType.paymentMethod',
            'mainPilgrim']);
        return $this->respondWithResource($invoice, 'Invoice is already set to pending');
    }

    $invoice->invoiceStatus = 'pending';
    $invoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $invoice->creationDateHijri = $this->getHijriDate();
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();
    $invoice->save();

    $metaForDiffOnly = [
        'creationDate' => $invoice->creationDate,
        'creationDateHijri' => $invoice->creationDateHijri,
    ];

    $changedData = $invoice->getChangedData($oldData, array_merge($invoice->fresh()->toArray(), $metaForDiffOnly));
    $invoice->changed_data = $changedData;
    $invoice->save();

    $invoice->load(['busInvoice', 'pilgrims', 'ihramSupplies' ,'paymentMethodType.paymentMethod',
            'mainPilgrim']);
    return $this->respondWithResource($invoice, 'Invoice set to pending');
}

public function approved($id)
{
    $this->authorize('manage_system');

    $invoice = IhramInvoice::find($id);
    if (!$invoice) {
        return response()->json(['message' => "Invoice not found."], 404);
    }

    $oldData = $invoice->toArray();

    if ($invoice->invoiceStatus === 'approved') {
        $invoice->load(['busInvoice', 'pilgrims', 'ihramSupplies' ,'paymentMethodType.paymentMethod',
            'mainPilgrim']);
        return $this->respondWithResource($invoice, 'Invoice is already set to approved');
    }

    $invoice->invoiceStatus = 'approved';
    $invoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $invoice->creationDateHijri = $this->getHijriDate();
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();
    $invoice->save();

    $metaForDiffOnly = [
        'creationDate' => $invoice->creationDate,
        'creationDateHijri' => $invoice->creationDateHijri,
    ];

    $changedData = $invoice->getChangedData($oldData, array_merge($invoice->fresh()->toArray(), $metaForDiffOnly));
    $invoice->changed_data = $changedData;
    $invoice->save();

    $invoice->load(['busInvoice', 'pilgrims', 'ihramSupplies' ,'paymentMethodType.paymentMethod',
            'mainPilgrim']);
    return $this->respondWithResource($invoice, 'Invoice set to approved');
}

public function rejected($id, Request $request)
{
    $this->authorize('manage_system');
    $validated = $request->validate([
        'reason' => 'nullable|string',
    ]);

    $invoice = IhramInvoice::find($id);
    if (!$invoice) {
        return response()->json(['message' => "Invoice not found."], 404);
    }

    $oldData = $invoice->toArray();

    if ($invoice->invoiceStatus === 'rejected') {
        $invoice->load(['busInvoice', 'pilgrims', 'ihramSupplies' ,'paymentMethodType.paymentMethod',
            'mainPilgrim']);
        return $this->respondWithResource($invoice, 'Invoice is already set to rejected');
    }

    $invoice->invoiceStatus = 'rejected';
    $invoice->reason = $validated['reason'] ?? null;
    $invoice->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $invoice->creationDateHijri = $this->getHijriDate();
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();
    $invoice->save();

    $metaForDiffOnly = [
        'creationDate' => $invoice->creationDate,
        'creationDateHijri' => $invoice->creationDateHijri,
    ];

    $changedData = $invoice->getChangedData($oldData, array_merge($invoice->fresh()->toArray(), $metaForDiffOnly));
    $invoice->changed_data = $changedData;
    $invoice->save();

    $invoice->load(['busInvoice', 'pilgrims', 'ihramSupplies' ,'paymentMethodType.paymentMethod',
            'mainPilgrim']);
    return $this->respondWithResource($invoice, 'Invoice set to rejected');
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
        $busInvoice = IhramInvoice::with([
            'paymentMethodType.paymentMethod',
            'mainPilgrim',
           'busInvoice', 'pilgrims', 'ihramSupplies'
        ])->findOrFail($id);


        if (round(floatval($validated['paidAmount']), 2) != round(floatval($busInvoice->total), 2)) {
            return response()->json([
                'message' => 'يجب أن يكون المبلغ المدفوع مساوياً تماماً لإجمالي الفاتورة',
                'total_amount' => $busInvoice->total,
                'paid_amount' => $validated['paidAmount'],
                'difference' => round(floatval($busInvoice->total), 2) - round(floatval($validated['paidAmount']), 2)
            ], 422);
        }

        if ($busInvoice->invoiceStatus === 'completed') {
            $this->loadCommonRelations($busInvoice);
            DB::commit();
            return $this->respondWithResource($busInvoice, 'فاتورة الحافلة مكتملة مسبقاً');
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

        $busInvoice->load(['pilgrims']);

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





        protected function getResourceClass(): string
    {
        return IhramInvoiceResource::class;
    }

}
