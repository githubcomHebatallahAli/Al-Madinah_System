<?php

namespace App\Http\Controllers\Admin;

use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Models\ShipmentInvoice;
use Illuminate\Http\JsonResponse;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ShipmentResource;
use App\Http\Requests\Admin\ShipmentInvoiceRequest;
use App\Http\Requests\Admin\UpdatePaidAmountRequest;
use App\Http\Resources\Admin\ShipmentInvoiceResource;
use App\Http\Resources\Admin\ShowAllShipmentInvoiceResource;

class ShipmentInvoiceController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

public function showAll(Request $request)
{
    $searchTerm = $request->input('search', '');
    $invoiceStatus = $request->input('invoice', '');
    $serviceId = $request->input('service_id');
    $branchId = $request->input('branch_id');

    $query = ShipmentInvoice::with(['shipment.supplier.company.service'])
        ->when($searchTerm, function ($q) use ($searchTerm) {
            $q->whereHas('shipment.supplier.company', function ($q3) use ($searchTerm) {
                $q3->where('name', 'like', "%{$searchTerm}%");
            });
        })
        ->when($serviceId, function ($q) use ($serviceId) {
            $q->whereHas('shipment.supplier.company', function ($q2) use ($serviceId) {
                $q2->where('service_id', $serviceId);
            });
        })
        ->when($branchId, function ($q) use ($branchId) {
            $q->whereHas('shipment.supplier.company.service', function ($q2) use ($branchId) {
                $q2->where('branch_id', $branchId);
            });
        })
        ->when($invoiceStatus === 'paid', function ($q) {
            $q->where('invoice', 'paid');
        })
        ->when($invoiceStatus === 'pending', function ($q) {
            $q->where('invoice', 'pending');
        })
        ->orderBy('created_at', 'desc');

    $ShipmentInvoices = $query->paginate(10);

    $totalPaidAmount = ShipmentInvoice::sum('paidAmount');
    $totalRemainingAmount = ShipmentInvoice::where('invoice', 'pending')->sum('remainingAmount');

    return response()->json([
        'data' => ShowAllShipmentInvoiceResource::collection($ShipmentInvoices),
        'pagination' => [
            'total' => $ShipmentInvoices->total(),
            'count' => $ShipmentInvoices->count(),
            'per_page' => $ShipmentInvoices->perPage(),
            'current_page' => $ShipmentInvoices->currentPage(),
            'total_pages' => $ShipmentInvoices->lastPage(),
            'next_page_url' => $ShipmentInvoices->nextPageUrl(),
            'prev_page_url' => $ShipmentInvoices->previousPageUrl(),
        ],
        'statistics' => [
            'paid_amount' => $totalPaidAmount,
            'remaining_amount' => $totalRemainingAmount,
        ],
        'message' => "تم عرض الفواتير بنجاح.",
    ]);
}


public function create(ShipmentInvoiceRequest $request): JsonResponse
{
    DB::beginTransaction();

    try {
        $shipment = Shipment::findOrFail($request->shipment_id);

        $discount = $request->discount ?? 0;
        $paidAmount = $request->paidAmount ?? 0;
        $totalAfterDiscount = $shipment->totalPrice - $discount;
        $remaining = $totalAfterDiscount - $paidAmount;
        $invoiceStatus = $remaining <= 0 ? 'paid' : 'pending';

        $invoiceData = array_merge([
            'shipment_id'             => $shipment->id,
            'payment_method_type_id'  => $request->payment_method_type_id,
            'discount'               => $discount,
            'totalPriceAfterDiscount' => $totalAfterDiscount,
            'paidAmount'             => $paidAmount,
            'remainingAmount'        => $remaining,
            'invoice'                => $invoiceStatus,
            'description'            => $request->description,

        ], $this->prepareCreationMetaData());

        $invoice = ShipmentInvoice::create($invoiceData);
          $invoice->load(['paymentMethodType', 'shipment','paymentMethodType.paymentMethod']);

        DB::commit();

        return $this->respondWithResource($invoice, 'تم إنشاء الفاتورة بنجاح');

    } catch (\Throwable $e) {
        DB::rollBack();
        return $this->handleError($e, 'حدث خطأ أثناء إنشاء الفاتورة');
    }
}


    public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
    {
        $invoice = ShipmentInvoice::findOrFail($id);

        $oldData = $invoice->toArray();

        $paidAmountToAdd = $request->paidAmount;
        $newPaidAmount = $invoice->paidAmount + $paidAmountToAdd;
        $debetAfterDiscount = $invoice->totalPriceAfterDiscount ?? 0;
        $remainingAmount = $debetAfterDiscount - $newPaidAmount;
        $invoiceStatus = $remainingAmount > 0 ? 'pending' : 'paid';

        $updateData = [
            'paidAmount'       => $newPaidAmount,
            'remainingAmount'  => max($remainingAmount, 0),
            'invoice'           => $invoiceStatus,
        ];

        $this->setUpdatedBy($updateData);

        $hasChanges = false;
        foreach ($updateData as $key => $value) {
            if ($invoice->$key != $value) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            return response()->json([
                'message' => 'لا يوجد تغييرات فعلية',
                'shipmentInvoice' => new ShipmentInvoiceResource($invoice->load(['shipment', 'paymentMethodType'])),
            ]);
        }

        $invoice->update($updateData);

        $changedData = $invoice->getChangedData($oldData, $invoice->fresh()->toArray());
        $invoice->changed_data = $changedData;
        $invoice->save();

        return response()->json([
            'message' => 'تم تحديث المبلغ المدفوع بنجاح',
            'shipmentInvoice' => new ShipmentInvoiceResource($invoice->load(['shipment', 'paymentMethodType'])),
            'totalPrice' => $invoice->totalPriceAfterDiscount,
            'discount' => $invoice->discount,
            'debetAfterDiscount' => $debetAfterDiscount,
            'paidAmount' => $invoice->paidAmount,
            'remainingAmount' => $invoice->remainingAmount,
        ]);
    }


public function edit(string $id)
{
    $ShipmentInvoice = ShipmentInvoice::with(['shipment', 'paymentMethodType','paymentMethodType.paymentMethod'])->find($id);

    if (!$ShipmentInvoice) {
        return response()->json([
            'message' => "ShipmentInvoice record not found."
        ], 404);
    }

    $totalShipmentInvoicePrice = $ShipmentInvoice->totalPriceAfterDiscount ?? 0;
    $paidAmount = $ShipmentInvoice->paidAmount ?? 0;
    $discount = $ShipmentInvoice->discount ?? 0;

    $ShipmentInvoiceAfterDiscount = $totalShipmentInvoicePrice;
    $remainingAmount = max(0, $ShipmentInvoiceAfterDiscount - $paidAmount);

    return response()->json([
        'message' => 'ShipmentInvoice details fetched successfully',
        'ShipmentInvoice' => new ShipmentInvoiceResource($ShipmentInvoice),

        'totalShipmentInvoicePrice' => $totalShipmentInvoicePrice,
        'discount' => $discount,
        'ShipmentInvoiceAfterDiscount' => $ShipmentInvoiceAfterDiscount,
        'paidAmount' => $paidAmount,
        'remainingAmount' => $remainingAmount,
    ]);
}



public function update(ShipmentInvoiceRequest $request, $id): JsonResponse
{
    DB::beginTransaction();

    try {
        $invoice = ShipmentInvoice::findOrFail($id);
        $shipment = Shipment::findOrFail($request->shipment_id);

        $discount = $request->discount ?? $invoice->discount;
        $paidAmount = $request->paidAmount ?? $invoice->paidAmount;
        $totalAfterDiscount = $shipment->totalPrice - $discount;
        $remaining = $totalAfterDiscount - $paidAmount;
        $invoiceStatus = $remaining <= 0 ? 'paid' : 'pending';

        $oldData = $invoice->toArray();

        $updateData = array_merge([
            'shipment_id'             => $shipment->id,
            'payment_method_type_id'  => $request->payment_method_type_id,
            'discount'                => $discount,
            'totalPriceAfterDiscount' => $totalAfterDiscount,
            'paidAmount'              => $paidAmount,
            'remainingAmount'         => $remaining,
            'invoice'                 => $invoiceStatus,
            'description'             => $request->description,
        ], $this->prepareUpdateMeta($request, $invoice->status));

        $hasChanges = false;
        foreach ($updateData as $key => $value) {
            if ($invoice->$key != $value) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            $invoice->load(['paymentMethodType', 'shipment', 'paymentMethodType.paymentMethod']);
            return $this->respondWithResource($invoice, 'لا يوجد تغييرات فعلية');
        }

        $invoice->update($updateData);

        $changedData = $invoice->getChangedData($oldData, $invoice->fresh()->toArray());
        $invoice->changed_data = $changedData;
        $invoice->save();


        $invoice->load(['paymentMethodType', 'shipment', 'paymentMethodType.paymentMethod']);

        DB::commit();

        return $this->respondWithResource($invoice, 'تم تحديث الفاتورة بنجاح');

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث الفاتورة',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    public function active(string $id)
    {
         $this->authorize('manage_users');
        $ShipmentInvoice = ShipmentInvoice::findOrFail($id);

        return $this->changeStatusSimple($ShipmentInvoice, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_users');
        $ShipmentInvoice = ShipmentInvoice::findOrFail($id);

        return $this->changeStatusSimple($ShipmentInvoice, 'notActive');
    }


   protected function getResourceClass(): string
    {
        return ShipmentInvoiceResource::class;
    }


}
