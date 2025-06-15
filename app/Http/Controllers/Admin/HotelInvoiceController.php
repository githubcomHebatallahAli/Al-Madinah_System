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

    public function showAllWithPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $query = HotelInvoice::query();

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('bus_trip_id')) {
            $query->where('bus_trip_id', $request->bus_trip_id);
        }

        $hotelInvoices = $query->with(['hotel', 'busTrip'])->orderBy('created_at', 'desc')->paginate(10);
        $totalPaidAmount = HotelInvoice::sum('paidAmount');

        return response()->json([
            'data' => HotelInvoiceResource::collection($hotelInvoices),
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

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        if ($request->filled('bus_trip_id')) {
            $query->where('bus_trip_id', $request->bus_trip_id);
        }

        $hotelInvoices = $query->with(['hotel', 'busTrip'])->orderBy('created_at', 'desc')->get();
        $totalPaidAmount = HotelInvoice::sum('paidAmount');

        return response()->json([
            'data' => HotelInvoiceResource::collection($hotelInvoices),
            'statistics' => [
                'paid_amount' => $totalPaidAmount,
            ],
            'message' => "Show All Hotel Invoices."
        ]);
    }

    public function edit(string $id)
    {
        $this->authorize('manage_system');

        $hotelInvoice = HotelInvoice::with([
            'pilgrims',
            'busTrip',
            'hotel'
        ])->find($id);

        if (!$hotelInvoice) {
            return response()->json(['message' => "Hotel Invoice not found."], 404);
        }

        return $this->respondWithResource($hotelInvoice, "Hotel Invoice retrieved for editing.");
    }

    public function update(HotelInvoiceRequest $request, $id)
    {
        $this->authorize('manage_system');

        $hotelInvoice = HotelInvoice::with(['pilgrims', 'busTrip', 'hotel'])->findOrFail($id);
        $oldData = $hotelInvoice->toArray();

        $data = array_merge([
            'discount' => $this->ensureNumeric($request->input('discount')),
            'tax' => $this->ensureNumeric($request->input('tax')),
            'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
        ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareUpdateMetaData());

        DB::beginTransaction();

        try {
            // تحديث بيانات الفاتورة الأساسية
            $hotelInvoice->update($data);

            // معالجة المعتمرين إذا كانت موجودة في الطلب
            if ($request->has('pilgrims')) {
                $pilgrimsData = [];
                $incompletePilgrims = $hotelInvoice->incomplete_pilgrims ?? [];

                foreach ($request->pilgrims as $pilgrim) {
                    $existingPilgrim = $this->findOrCreatePilgrim($pilgrim);

                    if (!$existingPilgrim) {
                        if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                            $incompletePilgrims[] = $pilgrim;
                            continue;
                        }
                    }

                    $pilgrimsData[$existingPilgrim->id] = [
                        'status' => 'booked',
                        'creationDateHijri' => $this->getHijriDate(),
                        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    ];
                }

                // مزامنة بيانات المعتمرين
                $hotelInvoice->pilgrims()->sync($pilgrimsData);
                $hotelInvoice->update(['incomplete_pilgrims' => !empty($incompletePilgrims) ? $incompletePilgrims : null]);
            }

            // حساب التكلفة الإجمالية
            $hotelInvoice->calculateTotal();

            DB::commit();

            return $this->respondWithResource($hotelInvoice->load([
                'pilgrims', 'busTrip', 'hotel', 'paymentMethodType'
            ]), "تم تحديث فاتورة الفندق بنجاح");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()], 500);
        }
    }

    protected function findOrCreatePilgrim(array $pilgrimData)
    {
        $existingPilgrim = null;

        if (!empty($pilgrimData['idNum'])) {
            $existingPilgrim = Pilgrim::where('idNum', $pilgrimData['idNum'])->first();
        } elseif (!empty($pilgrimData['phoNum'])) {
            $existingPilgrim = Pilgrim::where('phoNum', $pilgrimData['phoNum'])->first();
        }

        if (!$existingPilgrim && isset($pilgrimData['name'], $pilgrimData['nationality'], $pilgrimData['gender'])) {
            $existingPilgrim = Pilgrim::create([
                'idNum' => $pilgrimData['idNum'] ?? null,
                'name' => $pilgrimData['name'],
                'phoNum' => $pilgrimData['phoNum'] ?? null,
                'nationality' => $pilgrimData['nationality'],
                'gender' => $pilgrimData['gender'],
            ]);
        }

        return $existingPilgrim;
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

    public function create(HotelInvoiceRequest $request)
    {
        $this->authorize('manage_system');

        $data = array_merge([
            'discount' => $this->ensureNumeric($request->input('discount')),
            'tax' => $this->ensureNumeric($request->input('tax')),
            'paidAmount' => $this->ensureNumeric($request->input('paidAmount')),
            'subtotal' => 0,
            'total' => 0,
        ], $request->except(['discount', 'tax', 'paidAmount', 'pilgrims']), $this->prepareCreationMetaData());

        DB::beginTransaction();

        try {
            $hotelInvoice = HotelInvoice::create($data);
            $pilgrimsData = [];
            $incompletePilgrims = [];

            if ($request->has('pilgrims')) {
                foreach ($request->pilgrims as $pilgrim) {
                    $existingPilgrim = $this->findOrCreatePilgrim($pilgrim);

                    if (!$existingPilgrim) {
                        if (!isset($pilgrim['name'], $pilgrim['nationality'], $pilgrim['gender'])) {
                            $incompletePilgrims[] = $pilgrim;
                            continue;
                        }
                    }

                    $pilgrimsData[$existingPilgrim->id] = [
                        'status' => 'booked',
                        'creationDateHijri' => $this->getHijriDate(),
                        'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    ];
                }

                $hotelInvoice->pilgrims()->sync($pilgrimsData);
            }

            if (!empty($incompletePilgrims)) {
                $hotelInvoice->update(['incomplete_pilgrims' => $incompletePilgrims]);
            }

       
            $hotelInvoice->calculateTotal();

            DB::commit();

            return response()->json([
                'message' => 'تم إنشاء فاتورة الفندق بنجاح',
                'invoice' => new HotelInvoiceResource($hotelInvoice->load([
                    'pilgrims', 'busTrip', 'hotel', 'paymentMethodType'
                ])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()], 500);
        }
    }

    public function calculateTotal(HotelInvoice $hotelInvoice)
    {
        $hotel = $hotelInvoice->hotel;
        $numDays = $hotelInvoice->numDay ?? 1;
        $pilgrimsCount = $hotelInvoice->pilgrims()->count();

        if ($hotelInvoice->need === 'room') {
            // إذا كان الاختيار غرفة، يتم ضرب سعر الغرفة في عدد الأيام
            $subtotal = $hotel->roomPrice * $numDays;
        } else {
            // إذا كان الاختيار سرير، يتم ضرب سعر السرير في عدد الأفراد ثم في عدد الأيام
            $subtotal = $hotel->bedPrice * $pilgrimsCount * $numDays;
        }

        $hotelInvoice->subtotal = $subtotal;
        $hotelInvoice->total = $subtotal - ($hotelInvoice->discount ?? 0) + ($hotelInvoice->tax ?? 0);
        $hotelInvoice->save();
    }

    public function addPilgrimsFromBusInvoice(Request $request, HotelInvoice $hotelInvoice)
    {
        $this->authorize('manage_system');

        $request->validate([
            'bus_invoice_id' => 'required|exists:bus_invoices,id'
        ]);

        $busInvoice = BusInvoice::with('pilgrims')->find($request->bus_invoice_id);
        $pilgrimsData = [];

        foreach ($busInvoice->pilgrims as $pilgrim) {
            $pilgrimsData[$pilgrim->id] = [
                'status' => 'booked',
                'creationDateHijri' => $this->getHijriDate(),
                'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            ];
        }

        DB::beginTransaction();

        try {
            $hotelInvoice->pilgrims()->syncWithoutDetaching($pilgrimsData);
            $hotelInvoice->calculateTotal();
            DB::commit();

            return response()->json([
                'message' => 'تم إضافة المعتمرين من فاتورة الباص بنجاح',
                'invoice' => new HotelInvoiceResource($hotelInvoice->load('pilgrims'))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في إضافة المعتمرين: ' . $e->getMessage()], 500);
        }
    }

    public function checkPilgrimByIdNum(Request $request)
    {
        $this->authorize('manage_system');

        $request->validate([
            'idNum' => 'required|string'
        ]);

        $pilgrim = Pilgrim::where('idNum', $request->idNum)->first();

        if (!$pilgrim) {
            return response()->json([
                'exists' => false,
                'message' => 'لم يتم العثور على معتمر بهذا الرقم'
            ]);
        }

        return response()->json([
            'exists' => true,
            'pilgrim' => $pilgrim,
            'message' => 'تم العثور على المعتمر'
        ]);
    }

    protected function ensureNumeric($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return is_numeric($value) ? $value : 0;
    }

    protected function getResourceClass(): string
    {
        return HotelInvoiceResource::class;
    }
}
