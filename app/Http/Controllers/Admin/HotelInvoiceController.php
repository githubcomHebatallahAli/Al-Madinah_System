<?php

namespace App\Http\Controllers\Admin;


use App\Models\Pilgrim;
use App\Models\BusInvoice;
use App\Models\HotelInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
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


        if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
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

          if ($request->filled('paymentStatus')) {
            $query->where('paymentStatus', $request->paymentStatus);
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

public function create(HotelInvoiceRequest $request)
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
            new HotelInvoiceResource($invoice->load(['hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'])),
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
         'hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'
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




    public function approve(HotelInvoice $hotelInvoice)
    {
        $hotelInvoice->update(['invoiceStatus' => 'approved']);
        $hotelInvoice->calculateTotal();

        return response()->json([
            'message' => 'تم اعتماد الفاتورة بنجاح'
        ]);
    }

    public function reject(HotelInvoice $hotelInvoice)
    {
        $hotelInvoice->update([
            'invoiceStatus' => 'rejected',
            'subtotal' => 0,
            'total' => 0,
            'paidAmount' => 0
        ]);

        return response()->json([
            'message' => 'تم رفض الفاتورة بنجاح'
        ]);
    }




        protected function getResourceClass(): string
    {
        return HotelInvoiceResource::class;
    }


protected function attachBusPilgrims(HotelInvoice $invoice, $busInvoiceId)
{
    if (empty($busInvoiceId)) {
        return;
    }

    $busInvoice = BusInvoice::with('pilgrims')->find($busInvoiceId);

    if (!$busInvoice) {
        throw new \Exception('عفواً، فاتورة الباص المحددة غير موجودة!');
    }

    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $pilgrimsData = $busInvoice->pilgrims->mapWithKeys(function ($pilgrim) use ($currentDate, $hijriDate) {
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


protected function hasPilgrimsChanges(HotelInvoice $invoice, array $newPilgrims): bool
{
    $currentPilgrims = $invoice->pilgrims()->pluck('pilgrims.id')->toArray();

    // جمع معرفات الحجاج الجديدة (تجاهل الذين ليس لديهم idNum)
    $newPilgrimsIds = collect($newPilgrims)
        ->filter(fn($p) => !empty($p['idNum']))
        ->pluck('idNum')
        ->toArray();

    // الحجاج الحاليون الذين لديهم idNum
    $currentWithIdNum = $invoice->pilgrims()
        ->whereNotNull('idNum')
        ->pluck('idNum')
        ->toArray();

    if (count(array_diff($currentWithIdNum, $newPilgrimsIds)) > 0) return true;
    if (count(array_diff($newPilgrimsIds, $currentWithIdNum)) > 0) return true;

    // التحقق من عدد الحجاج بدون idNum (الأطفال)
    $currentChildrenCount = $invoice->pilgrims()
        ->whereNull('idNum')
        ->count();

    $newChildrenCount = collect($newPilgrims)
        ->filter(fn($p) => empty($p['idNum']))
        ->count();

    return $currentChildrenCount != $newChildrenCount;
}


protected function syncPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    foreach ($pilgrims as $pilgrim) {
        if (empty($pilgrim['idNum'])) {
            // إنشاء حاج جديد (طفل) بدون idNum
            $existingPilgrim = Pilgrim::create([
                'name' => $pilgrim['name'],
                'nationality' => $pilgrim['nationality'],
                'gender' => $pilgrim['gender'],
                'phoNum' => $pilgrim['phoNum'] ?? null,
                'idNum' => null
            ]);
        } else {
            // البحث عن الحاج الموجود أو إنشائه
            $existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])->first();

            if (!$existingPilgrim) {
                $existingPilgrim = Pilgrim::create([
                    'idNum' => $pilgrim['idNum'],
                    'name' => $pilgrim['name'],
                    'nationality' => $pilgrim['nationality'],
                    'gender' => $pilgrim['gender'],
                    'phoNum' => $pilgrim['phoNum'] ?? null
                ]);
            }
        }

        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $existingPilgrim->id)->first();

        $pilgrimsData[$existingPilgrim->id] = [
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->sync($pilgrimsData);
}

protected function attachPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        // التحقق من البيانات الأساسية للأطفال (بدون idNum أو phone)
        if (empty($pilgrim['idNum'])) {
            if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع على الأقل');
            }

            // إنشاء حاج جديد بدون idNum أو phone
            $p = Pilgrim::create([
                'name' => $pilgrim['name'],
                'nationality' => $pilgrim['nationality'],
                'gender' => $pilgrim['gender'],
                'phoNum' => $pilgrim['phoNum'] ?? null,
                'idNum' => null // صراحة تعيين كقيمة null
            ]);
        } else {
            // البحث أو الإنشاء للحاج العادي
            $p = Pilgrim::firstOrCreate(
                ['idNum' => $pilgrim['idNum']],
                [
                    'name' => $pilgrim['name'] ?? null,
                    'nationality' => $pilgrim['nationality'] ?? null,
                    'gender' => $pilgrim['gender'] ?? null,
                    'phoNum' => $pilgrim['phoNum'] ?? null
                ]
            );
        }

        $pilgrimsData[$p->id] = [
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->attach($pilgrimsData);
}
}
