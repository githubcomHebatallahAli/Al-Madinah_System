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

    // حفظ البيانات الأصلية للمقارنة بعد الكاست
    $originalData = $invoice->attributesToArray();

    // القيم المقترحة
    $paidAmount = $extra['paidAmount'] ?? $invoice->paidAmount;
    $discount = $extra['discount'] ?? $invoice->discount;
    $tax = $extra['tax'] ?? $invoice->tax;

    if ($status === 'completed') {
        // نسخة مؤقتة من الفاتورة للحساب فقط
        $tempInvoice = clone $invoice;
        $tempInvoice->discount = $discount;
        $tempInvoice->tax = $tax;
        $tempInvoice->calculateTotals();

        // التحقق من تطابق المبلغ المدفوع مع الإجمالي النهائي
        if (round($paidAmount, 2) !== round($tempInvoice->total, 2)) {
            return response()->json([
                'message' => 'لا يمكن إكمال الفاتورة: المبلغ المدفوع لا يساوي الإجمالي',
                'required_amount' => number_format($tempInvoice->total, 2),
                'paid_amount' => number_format($paidAmount, 2),
                'current_status' => $invoice->invoiceStatus
            ], 422);
        }

        // فقط بعد النجاح يتم حفظ القيم الجديدة
        $invoice->paidAmount = $paidAmount;
        $invoice->discount = $discount;
        $invoice->tax = $tax;

        if ($invoice->invoiceStatus !== 'completed') {
            $invoice->payment_method_type_id = $extra['payment_method_type_id'] ?? $invoice->payment_method_type_id;
        }
    }

    // تغيير حالة الفاتورة إذا لزم
    if ($invoice->invoiceStatus !== $status || $status === 'completed') {
        $invoice->invoiceStatus = $status;

        if ($status === 'rejected') {
            $invoice->reason = $extra['reason'] ?? null;
        }
    }

    // تحديث بيانات التعديل
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();

    // حساب الإجماليات وتطبيق الكاست
    $invoice->calculateTotals();

    // تتبع التعديلات
    $changedData = $this->buildInvoiceChanges($invoice, $originalData);
    if (!empty($changedData)) {
        $invoice->changed_data = $changedData;
    }

    // حفظ الفاتورة
    $invoice->save();

    // تحميل العلاقات المطلوبة
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
