<?php

namespace App\Http\Controllers\Admin;


use App\Models\Pilgrim;
use App\Models\BusInvoice;
use App\Models\HotelInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HotelInvoiceRequest;
use App\Http\Resources\Admin\HotelInvoiceResource;


class HotelInvoiceController extends Controller
{
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

    $data = array_merge($request->only([
        'hotel_id',
        'trip_id',
        'bus_invoice_id',
        'payment_method_type_id',
        'need',
        'numDay',
        'checkOutDateHijri',
        'checkOutDate',
        'checkInDateHijri',
        'checkInDate',
        'description',
        'discount',
        'tax',
        'roomNum',
        'reason',
        'paidAmount',
        'invoiceStatus',
        'paymentStatus',
        'bookingSource'
    ]), $this->prepareCreationMetaData());

    DB::beginTransaction();
    try {
        $invoice = HotelInvoice::create($data);


        if ($request->has('pilgrims')) {
            $this->attachPilgrims($invoice, $request->pilgrims);
        }

      
        if ($request->has('bus_invoice_id')) {
            $this->attachBusPilgrims($invoice, $request->bus_invoice_id);
        }

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

    public function destroy(HotelInvoice $hotelInvoice)
    {
        $hotelInvoice->delete();
        return response()->json([
            'message' => 'تم حذف الفاتورة بنجاح'
        ]);
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

    // دوال مساعدة خاصة بربط الحجاج
    protected function attachPilgrims(HotelInvoice $invoice, array $pilgrims)
    {
        $pilgrimsData = [];
        foreach ($pilgrims as $pilgrim) {
            $p = Pilgrim::firstOrCreate(
                ['idNum' => $pilgrim['idNum']],
                $pilgrim
            );
            $pilgrimsData[$p->id] = ['type' => $pilgrim['type']];
        }
        $invoice->pilgrims()->attach($pilgrimsData);
    }

    protected function attachBusPilgrims(HotelInvoice $invoice, $busInvoiceId)
    {
        $busInvoice = BusInvoice::with('pilgrims')->findOrFail($busInvoiceId);
        $pilgrimsData = $busInvoice->pilgrims->mapWithKeys(function ($pilgrim) {
            return [$pilgrim->id => ['type' => 'bus']];
        });
        $invoice->pilgrims()->attach($pilgrimsData);
    }

    protected function syncPilgrims(HotelInvoice $invoice, array $pilgrims)
    {
        $pilgrimsData = [];
        foreach ($pilgrims as $pilgrim) {
            $p = Pilgrim::firstOrCreate(
                ['idNum' => $pilgrim['idNum']],
                $pilgrim
            );
            $pilgrimsData[$p->id] = ['type' => $pilgrim['type']];
        }
        $invoice->pilgrims()->sync($pilgrimsData);
    }
}
