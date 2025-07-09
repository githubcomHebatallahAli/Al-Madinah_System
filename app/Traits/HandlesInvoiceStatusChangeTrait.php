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

    // حفظ البيانات الأصلية للمقارنة
    $originalData = $invoice->attributesToArray();

    // القيم المقترحة من الريكوست أو الحالية
    $paidAmount = $extra['paidAmount'] ?? $invoice->paidAmount;
    $discount = $extra['discount'] ?? $invoice->discount ?? 0;
    $taxRate = $extra['tax'] ?? $invoice->tax ?? 0;

    if ($status === 'completed') {
        // نحسب subtotal من الدوال الأصلية
        $busSubtotal = $invoice->calculateBusTotal();
        $ihramSubtotal = $invoice->calculateIhramTotal();
        $hotelTotal = $invoice->calculateHotelTotal();

        $subtotal = $busSubtotal + $ihramSubtotal + $hotelTotal;

        $totalAfterDiscount = max($subtotal - $discount, 0);
        $taxAmount = round($totalAfterDiscount * ($taxRate / 100), 2);
        $expectedTotal = round($totalAfterDiscount + $taxAmount, 2);

        // التأكد من التطابق التام (بدقة 2 رقم عشرية)
        if (round($paidAmount, 2) !== $expectedTotal) {
            return response()->json([
                'message' => 'لا يمكن إكمال الفاتورة: المبلغ المدفوع لا يساوي الإجمالي',
                'required_amount' => number_format($expectedTotal, 2),
                'paid_amount' => number_format($paidAmount, 2),
                'current_status' => $invoice->invoiceStatus
            ], 422);
        }

        // فقط بعد التحقق، نعدل القيم
        $invoice->busSubtotal = $busSubtotal;
        $invoice->ihramSubtotal = $ihramSubtotal;
        $invoice->subtotal = $subtotal;

        $invoice->paidAmount = $paidAmount;
        $invoice->discount = $discount;
        $invoice->tax = $taxRate;

        if ($invoice->invoiceStatus !== 'completed') {
            $invoice->payment_method_type_id = $extra['payment_method_type_id'] ?? $invoice->payment_method_type_id;
        }
    }

    // تحديث الحالة
    if ($invoice->invoiceStatus !== $status || $status === 'completed') {
        $invoice->invoiceStatus = $status;

        if ($status === 'rejected') {
            $invoice->reason = $extra['reason'] ?? null;
        }
    }

    // تحديث معلومات التعديل
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();

    // إعادة حساب الإجماليات بنفس دالتك
    $invoice->calculateTotals();

    // تتبع التغييرات
    $changedData = $this->buildInvoiceChanges($invoice, $originalData);
    if (!empty($changedData)) {
        $invoice->changed_data = $changedData;
    }

    // الحفظ النهائي
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
