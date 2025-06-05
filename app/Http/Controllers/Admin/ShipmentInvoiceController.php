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
    // $this->authorize('showAll', ShipmentInvoice::class);

    $searchTerm = $request->input('search', '');
    $statusFilter = $request->input('status', '');

    $query = ShipmentInvoice::where('customerName', 'like', '%' . $searchTerm . '%');

    if ($statusFilter === 'paid') {
        $query->where('status', 'paid');
    } elseif ($statusFilter === 'unpaid') {
        $query->where('status', 'pending');
    }

    $ShipmentInvoices = $query->orderBy('created_at', 'desc')
                   ->paginate(10);


    $totalPaidAmount = ShipmentInvoice::sum('paidAmount');
    $totalRemainingAmount = ShipmentInvoice::where('status', 'pending')->sum('remainingAmount');

    return response()->json([
        'data' => $ShipmentInvoices->map(function ($ShipmentInvoice) {
            return [
                'id' => $ShipmentInvoice->id,
                'customerName' => $ShipmentInvoice->customerName,
                'status' => $ShipmentInvoice->status,
                'paidAmount' => $ShipmentInvoice->paidAmount,
                'remainingAmount' => $ShipmentInvoice->remainingAmount,
                'depetAfterDiscount' => $ShipmentInvoice->depetAfterDiscount,
                'creationDate' => $ShipmentInvoice->creationDate,
            ];
        }),
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
        'message' => "تم عرض الفواتير بنجاح."
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

    $paidAmountToAdd = $request->paidAmount;
    $invoice->paidAmount += $paidAmountToAdd;

    $debetAfterDiscount = $invoice->totalPriceAfterDiscount ?? 0;

    $remainingAmount = $debetAfterDiscount - $invoice->paidAmount;

    $invoice->remainingAmount = number_format(max($remainingAmount, 0), 2, '.', '');
    $invoice->status = $remainingAmount > 0 ? 'pending' : 'paid';

    $invoice->updated_by = auth()->id();
    $invoice->updated_by_type = get_class(auth()->user());

    $invoice->save();

    return response()->json([
        'message' => 'Paid amount updated successfully',
        'shipmentInvoice' => new ShipmentInvoiceResource($invoice->load(['shipment', 'paymentMethodType'])),
        'totalPrice' => number_format($invoice->totalPriceAfterDiscount, 2, '.', ''),
        'discount' => number_format($invoice->discount, 2, '.', ''),
        'debetAfterDiscount' => number_format($debetAfterDiscount, 2, '.', ''),
        'paidAmount' => number_format($invoice->paidAmount, 2, '.', ''),
        'remainingAmount' => number_format($invoice->remainingAmount, 2, '.', ''),
    ]);
}

public function edit(string $id)
{
    $ShipmentInvoice = ShipmentInvoice::with(['shipment', 'paymentMethodType'])->find($id);

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
        'ShipmentInvoice' => new ShipmentInvoiceResource($ShipmentInvoice->load('products')),

        'totalShipmentInvoicePrice' => number_format($totalShipmentInvoicePrice, 2, '.', ''),
        'discount' => number_format($discount, 2, '.', ''),
        'ShipmentInvoiceAfterDiscount' => number_format($ShipmentInvoiceAfterDiscount, 2, '.', ''),
        'paidAmount' => number_format($paidAmount, 2, '.', ''),
        'remainingAmount' => number_format($remainingAmount, 2, '.', ''),
    ]);
}


public function update(ShipmentInvoiceRequest $request, $id): JsonResponse
{
    DB::beginTransaction();

    try {
        $invoice = ShipmentInvoice::findOrFail($id);

        $shipment = Shipment::findOrFail($request->shipment_id);

        $discount = $request->discount ?? 0;
        $paidAmount = $request->paidAmount ?? $invoice->paidAmount;
        $totalAfterDiscount = $shipment->totalPrice - $discount;

        $remaining = $totalAfterDiscount - $paidAmount;

        $invoiceStatus = $remaining <= 0 ? 'paid' : 'pending';

        $invoice->update([
            'shipment_id'             => $shipment->id,
            'payment_method_type_id'  => $request->payment_method_type_id,
            'discount'                => $discount,
            'totalPriceAfterDiscount' => $totalAfterDiscount,
            'paidAmount'              => $paidAmount,
            'remainingAmount'         => $remaining,
            'invoice'                 => $invoiceStatus,
            'description'             => $request->description,
            'creationDate'            => $request->creationDate,
            'creationDateHijri'       => $request->creationDateHijri,
            'status'                  => $request->status ?? $invoice->status,
            'updated_by'              => auth()->id(),
            'updated_by_type'         => get_class(auth()->user()),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الفاتورة بنجاح',
            'data'    => $invoice
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث الفاتورة',
            'error'   => $e->getMessage()
        ], 500);
    }
}

   protected function getResourceClass(): string
    {
        return ShipmentInvoiceResource::class;
    }


}
