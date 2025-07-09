<?php

namespace App\Traits;

use App\Models\PaymentMethodType;

trait HandlesInvoiceStatusChangeTrait
{
public function changeInvoiceStatus($invoice, string $status, array $extra = []): \Illuminate\Http\JsonResponse
{
    $this->authorize('manage_system');

    if (!$invoice) {
        return response()->json(['message' => "Invoice not found."], 404);
    }

    $originalData = $invoice->attributesToArray();

    // لا نعدل على الفاتورة الأصلية قبل التحقق
    $proposedPaidAmount = $extra['paidAmount'] ?? $invoice->paidAmount;
    $proposedDiscount = $extra['discount'] ?? $invoice->discount;
    $proposedTax = $extra['tax'] ?? $invoice->tax;

    if ($status === 'completed') {
        $tempInvoice = clone $invoice;
        $tempInvoice->discount = $proposedDiscount;
        $tempInvoice->tax = $proposedTax;
        $tempInvoice->calculateTotals();

        if (round($proposedPaidAmount, 2) !== round($tempInvoice->total, 2)) {
            return response()->json([
                'message' => 'لا يمكن إكمال الفاتورة: المبلغ المدفوع لا يساوي الإجمالي',
                'required_amount' => number_format($tempInvoice->total, 2),
                'paid_amount' => number_format($proposedPaidAmount, 2),
                'current_status' => $invoice->invoiceStatus
            ], 422);
        }

        // ✅ فقط بعد النجاح نطبق القيم دي
        $invoice->paidAmount = $proposedPaidAmount;
        $invoice->discount = $proposedDiscount;
        $invoice->tax = $proposedTax;

        if ($invoice->invoiceStatus !== 'completed') {
            $invoice->payment_method_type_id = $extra['payment_method_type_id'] ?? $invoice->payment_method_type_id;
        }
    }

    // الحالة نفسها
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




protected function buildInvoiceChanges($invoice, $originalData): array
{
    $changes = [];

    foreach ($invoice->getDirty() as $field => $newValue) {
        if (array_key_exists($field, $originalData)) {
            $oldValue = $invoice->getAttributeValue($field); // old بعد cast
            $newValueCasted = $invoice->getAttribute($field); // new بعد cast

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
