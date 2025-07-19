<?php

namespace App\Traits;

use App\Models\BusTrip;
use Illuminate\Support\Facades\Log;

trait HandlesInvoiceStatusChangeTrait
{


    public function changeInvoiceStatus($invoice, string $status, array $extra = []): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage_system');

        if (!$invoice) {
            return response()->json(['message' => "Invoice not found."], 404);
        }

        if (in_array($status, ['rejected', 'absence'])) {
            $this->releaseInvoiceSeats($invoice);
        }

        $originalData = $invoice->only([
            'discount', 'tax', 'paidAmount', 'invoiceStatus',
            'subtotal', 'totalAfterDiscount', 'total'
        ]);

        $proposedPaidAmount = $extra['paidAmount'] ?? $invoice->paidAmount;
        $proposedDiscount = $extra['discount'] ?? $invoice->discount;
        $proposedTax = $extra['tax'] ?? $invoice->tax;

        if ($status === 'completed') {
            $tempInvoice = clone $invoice;
            $tempInvoice->discount = $proposedDiscount;
            $tempInvoice->tax = $proposedTax;
            $tempInvoice->calculateTotals();

            if (round($proposedPaidAmount, 2) !== round($tempInvoice->total, 2)) {
                $invoice->fill([
                    'discount' => $originalData['discount'],
                    'tax' => $originalData['tax'],
                    'paidAmount' => $originalData['paidAmount'],
                ]);

                return response()->json([
                    'message' => 'لا يمكن إكمال الفاتورة: المبلغ المدفوع لا يساوي الإجمالي',
                    'required_amount' => number_format($tempInvoice->total, 2),
                    'paid_amount' => number_format($proposedPaidAmount, 2),
                    'current_status' => $invoice->invoiceStatus
                ], 422);
            }

            $invoice->discount = $proposedDiscount;
            $invoice->tax = $proposedTax;
            $invoice->paidAmount = $proposedPaidAmount;

            if ($invoice->invoiceStatus !== 'completed') {
                $invoice->payment_method_type_id = $extra['payment_method_type_id'] ?? $invoice->payment_method_type_id;
            }
        }

        if ($invoice->invoiceStatus !== $status || $status === 'completed') {
            $invoice->invoiceStatus = $status;

            if ($status === 'rejected') {
                $invoice->reason = $extra['reason'] ?? null;
            }
        }

        $invoice->updated_by = $this->getUpdatedByIdOrFail();
        $invoice->updated_by_type = $this->getUpdatedByType();

        $invoice->calculateTotals();
        $changedData = $this->buildInvoiceChanges($invoice, $originalData);
        if (!empty($changedData)) {
            $invoice->changed_data = $changedData;
        }

        $invoice->save();
        $this->loadInvoiceRelations($invoice);

        return $this->respondWithResource(
            $invoice,
            $invoice->invoiceStatus === 'completed'
                ? 'تم إكمال الفاتورة بنجاح'
                : "تم تحديث حالة الفاتورة إلى {$status}"
        );
    }

    protected function releaseInvoiceSeats($invoice): void
    {
        try {
            if (!$invoice->bus_trip_id) {
                return;
            }

            $busTrip = BusTrip::find($invoice->bus_trip_id);
            if (!$busTrip) {
                return;
            }

            $pilgrims = $invoice->pilgrims()->withPivot('seatNumber')->get();

            foreach ($pilgrims as $pilgrim) {
                $seatNumbers = explode(',', $pilgrim->pivot->seatNumber);

                foreach ($seatNumbers as $seatNumber) {
                    if (!empty($seatNumber)) {
                        $this->updateSeatStatus($busTrip, $seatNumber, 'available');
                    }
                }
            }

            Log::info("تم تحرير مقاعد الفاتورة {$invoice->id} بنجاح");
        } catch (\Exception $e) {
            Log::error("فشل في تحرير مقاعد الفاتورة: " . $e->getMessage());
        }
    }


    protected function updateSeatStatus(BusTrip $busTrip, string $seatNumber, string $status): void
    {
        try {
            $busTrip->refresh();
            $seatMap = collect($busTrip->seatMap);

            $seatIndex = $seatMap->search(function ($item) use ($seatNumber) {
                return $item['seatNumber'] === $seatNumber;
            });

            if ($seatIndex !== false) {
                $updatedSeatMap = $seatMap->all();
                $updatedSeatMap[$seatIndex]['status'] = $status;

                $busTrip->seatMap = $updatedSeatMap;
                $busTrip->save();
            }
        } catch (\Exception $e) {
            Log::error("فشل في تحديث حالة المقعد {$seatNumber}: " . $e->getMessage());
            throw $e;
        }
    }

protected function buildInvoiceChanges($invoice, $originalData): array
{
    $changes = [];
    foreach ($invoice->getDirty() as $field => $newValue) {
        if (array_key_exists($field, $originalData)) {
            $oldValue = $invoice->getAttributeValue($field);
            $newValueCasted = $invoice->getAttribute($field);

            if ($oldValue !== $newValueCasted) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValueCasted
                ];
            }
        }
    }

    if (!empty($changes)) {
        $previousChanged = $invoice->changed_data ?? [];

        $changes['creationDate'] = [
            'old' => $previousChanged['creationDate']['new'] ?? $invoice->getAttribute('creationDate'),
            'new' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s')
        ];

        $changes['creationDateHijri'] = [
            'old' => $previousChanged['creationDateHijri']['new'] ?? $invoice->getAttribute('creationDateHijri'),
            'new' => $this->getHijriDate()
        ];
    }

    return $changes;
}

    protected function loadInvoiceRelations($invoice): void
    {
        $invoice->load([
            'pilgrims',
            'ihramSupplies',
            'paymentMethodType.paymentMethod',
            'mainPilgrim'
        ]);
    }
}
