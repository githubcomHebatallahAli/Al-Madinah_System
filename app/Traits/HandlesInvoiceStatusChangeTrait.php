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

    // التحقق من القيم الرقمية وتحويلها
    $paidAmount = isset($extra['paidAmount']) ? floatval($extra['paidAmount']) : $invoice->paidAmount;
    $discount = isset($extra['discount']) ? floatval($extra['discount']) : $invoice->discount;
    $tax = isset($extra['tax']) ? floatval($extra['tax']) : $invoice->tax;

    // حفظ البيانات الأصلية للمقارنة
    $originalData = $invoice->getOriginal();

    // إذا كانت الحالة الجديدة هي 'completed'
    if ($status === 'completed') {
        // نسخ مؤقت للفاتورة للحسابات
        $tempInvoice = clone $invoice;
        $tempInvoice->discount = $discount;
        $tempInvoice->tax = $tax;
        $tempInvoice->calculateTotals();

        // التحقق من تطابق المبلغ المدفوع مع الإجمالي
        if (round($paidAmount, 2) !== round($tempInvoice->total, 2)) {
            return response()->json([
                'message' => 'لا يمكن إكمال الفاتورة: المبلغ المدفوع لا يساوي الإجمالي',
                'required_amount' => number_format($tempInvoice->total, 2),
                'paid_amount' => number_format($paidAmount, 2),
                'current_status' => $invoice->invoiceStatus
            ], 422);
        }

        // إذا كان التحويل من حالة غير completed إلى completed
        if ($invoice->invoiceStatus !== 'completed') {
            $invoice->payment_method_type_id = $extra['payment_method_type_id'] ?? $invoice->payment_method_type_id;
        }
    }

    // تطبيق التحديثات إذا كانت الحالة تتغير أو عند الإكمال
    if ($invoice->invoiceStatus !== $status || $status === 'completed') {
        $invoice->invoiceStatus = $status;
        $invoice->paidAmount = $paidAmount;
        $invoice->discount = $discount;
        $invoice->tax = $tax;

        if ($status === 'rejected') {
            $invoice->reason = $extra['reason'] ?? null;
        }
    }

    // تحديث المعلومات الإدارية
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();

    // إعادة حساب الإجماليات
    $invoice->calculateTotals();

    // تسجيل التغييرات
    $changedData = $this->buildInvoiceChanges($invoice, $originalData);
    if (!empty($changedData)) {
        $invoice->changed_data = $changedData;
    }

    // الحفظ النهائي
    $invoice->save();

    // تحميل العلاقات
    $this->loadInvoiceRelations($invoice);

    return $this->respondWithResource($invoice,
        $invoice->invoiceStatus === 'completed'
            ? "تم إكمال الفاتورة بنجاح"
            : "تم تحديث حالة الفاتورة إلى {$status}"
    );
}

    protected function buildInvoiceChanges($invoice, $originalData): array
    {
        $changes = [];

        foreach ($invoice->getDirty() as $field => $newValue) {
            if (array_key_exists($field, $originalData)) {
                $changes[$field] = [
                    'old' => $originalData[$field],
                    'new' => $newValue
                ];
            }
        }

        if (!empty($changes)) {
            $previousChanged = $invoice->changed_data ?? [];

            $changes['creationDate'] = [
                'old' => $previousChanged['creationDate']['new'] ?? $originalData['creationDate'],
                'new' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s')
            ];

            $changes['creationDateHijri'] = [
                'old' => $previousChanged['creationDateHijri']['new'] ?? $originalData['creationDateHijri'],
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
