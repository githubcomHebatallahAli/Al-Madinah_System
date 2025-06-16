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


class HotelInvoiceController extends Controller
{
     use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;
    public function showAllWithoutPaginate(Request $request)
    {
        $query = HotelInvoice::with(['hotel', 'trip', 'busInvoice', 'paymentMethodType']);

        // الفلترة
        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('status')) {
            $query->where('invoiceStatus', $request->status);
        }

        $invoices = $query->latest()->paginate(10);

        return response()->json([
            'data' => HotelInvoiceResource::collection($invoices),
            'message' => 'تم جلب الفواتير بنجاح'
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

        if ($request->has('bus_invoice_id')) {
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

    public function edit(HotelInvoice $hotelInvoice)
    {
        return response()->json([
            'data' => new HotelInvoiceResource($hotelInvoice->load([
                'hotel', 'trip', 'busInvoice', 'paymentMethodType', 'pilgrims'
            ])),
            'message' => 'تم جلب الفاتورة بنجاح'
        ]);
    }

    public function update(HotelInvoiceRequest $request, HotelInvoice $hotelInvoice)
    {
        // منع التعديل إذا كانت الفاتورة معتمدة أو مكتملة
        if (in_array($hotelInvoice->invoiceStatus, ['approved', 'completed'])) {
            return response()->json([
                'message' => 'لا يمكن تعديل فاتورة معتمدة أو مكتملة'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $hotelInvoice->update($request->validated());

            // مزامنة الحجاج إذا تم إرسالهم
            if ($request->has('pilgrims')) {
                $this->syncPilgrims($hotelInvoice, $request->pilgrims);
            }

            $hotelInvoice->calculateTotal();
            DB::commit();

            return response()->json([
                'data' => new HotelInvoiceResource($hotelInvoice),
                'message' => 'تم تحديث الفاتورة بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
            ], 500);
        }
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

/**
 * ربط الحجاج مع استخدام التواريخ الهجرية
 */
protected function attachPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $pilgrimsData = [];
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    foreach ($pilgrims as $pilgrim) {
        // التحقق من البيانات المطلوبة للحجاج الجدد
        if (!Pilgrim::where('idNum', $pilgrim['idNum'])->exists()) {
            if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                throw new \Exception('بيانات غير مكتملة للحاج الجديد: يرجى إدخال الاسم، الجنسية، والنوع');
            }
        }

        // إنشاء أو جلب الحاج
        $p = Pilgrim::firstOrCreate(
            ['idNum' => $pilgrim['idNum']],
            [
                'name' => $pilgrim['name'] ?? null,
                'nationality' => $pilgrim['nationality'] ?? null,
                'gender' => $pilgrim['gender'] ?? null,
                'phoNum' => $pilgrim['phoNum'] ?? null
            ]
        );

        // إعداد بيانات الربط
        $pilgrimsData[$p->id] = [
            'type' => $pilgrim['type'],
            'creationDate' => $currentDate,
            'creationDateHijri' => $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->attach($pilgrimsData);
}

/**
 * ربط حجاج الباص مع التواريخ المخصصة
 */
protected function attachBusPilgrims(HotelInvoice $invoice, $busInvoiceId)
{
    $busInvoice = BusInvoice::with('pilgrims')->findOrFail($busInvoiceId);
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $pilgrimsData = $busInvoice->pilgrims->mapWithKeys(function ($pilgrim) use ($currentDate, $hijriDate) {
        return [
            $pilgrim->id => [
                'type' => 'bus',
                'creationDate' => $currentDate,
                'creationDateHijri' => $hijriDate,
                'changed_data' => null
            ]
        ];
    });

    $invoice->pilgrims()->attach($pilgrimsData->toArray());
}

/**
 * مزامنة الحجاج مع الحفاظ على التواريخ الأصلية
 */
protected function syncPilgrims(HotelInvoice $invoice, array $pilgrims)
{
    $hijriDate = $this->getHijriDate();
    $currentDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $pilgrimsData = [];

    foreach ($pilgrims as $pilgrim) {
        $existingPilgrim = Pilgrim::where('idNum', $pilgrim['idNum'])->first();

        if (!$existingPilgrim) {
            if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                throw new \Exception('بيانات غير مكتملة للحاج الجديد');
            }

            $existingPilgrim = Pilgrim::create([
                'idNum' => $pilgrim['idNum'],
                'name' => $pilgrim['name'],
                'nationality' => $pilgrim['nationality'],
                'gender' => $pilgrim['gender'],
                'phoNum' => $pilgrim['phoNum'] ?? null
            ]);
        }

        // الحفاظ على التواريخ الأصلية إذا كانت موجودة
        $existingPivot = $invoice->pilgrims()->where('pilgrim_id', $existingPilgrim->id)->first();

        $pilgrimsData[$existingPilgrim->id] = [
            'type' => $pilgrim['type'],
            'creationDate' => $existingPivot->pivot->creationDate ?? $currentDate,
            'creationDateHijri' => $existingPivot->pivot->creationDateHijri ?? $hijriDate,
            'changed_data' => null
        ];
    }

    $invoice->pilgrims()->sync($pilgrimsData);
}


        protected function getResourceClass(): string
    {
        return HotelInvoiceResource::class;
    }
}
