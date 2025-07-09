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

    $originalData = $invoice->getOriginal();

    // 1. تطبيق جميع التحديثات المالية أولاً (لجميع الحالات)
    if (array_key_exists('paidAmount', $extra)) {
        $invoice->paidAmount = $this->ensureNumeric($extra['paidAmount']);
    }

    if (array_key_exists('discount', $extra)) {
        $invoice->discount = $this->ensureNumeric($extra['discount']);
    }

    if (array_key_exists('tax', $extra)) {
        $invoice->tax = $this->ensureNumeric($extra['tax']);
    }

    if (array_key_exists('payment_method_type_id', $extra)) {
        $invoice->payment_method_type_id = $extra['payment_method_type_id'];
    }

    // 2. التحقق من حالة الإكمال
    $isStatusChanging = ($invoice->invoiceStatus !== $status);
    $isCompleting = ($status === 'completed');

    if ($isCompleting) {
        // حساب الإجماليات أولاً
        $invoice->calculateTotals();

        // التحقق من تطابق المبلغ المدفوع مع الإجمالي
        if (round($invoice->paidAmount, 2) !== round($invoice->total, 2)) {
            return response()->json([
                'message' => 'لا يمكن اكتمال الفاتورة إلا إذا كان المبلغ المدفوع مساوياً لإجمالي الفاتورة',
                'paidAmount' => number_format($invoice->paidAmount, 2),
                'total' => number_format($invoice->total, 2),
                'required_amount' => number_format($invoice->total, 2)
            ], 422);
        }
    }

    // 3. تطبيق تغيير الحالة إذا لزم الأمر
    if ($isStatusChanging) {
        $invoice->invoiceStatus = $status;

        if ($status === 'rejected') {
            $invoice->reason = $extra['reason'] ?? null;
        }
    }

    // 4. تحديث المعلومات الإدارية
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();

    // 5. إعادة حساب الإجماليات (لضمان تحديث جميع القيم)
    $invoice->calculateTotals();

    // 6. بناء بيانات التغيير
    $changedData = $this->buildInvoiceChanges($invoice, $originalData);

    if (!empty($changedData)) {
        $invoice->changed_data = $changedData;
    }

    // 7. الحفظ النهائي
    $invoice->save();

    // 8. تحميل العلاقات للإرجاع
    $this->loadInvoiceRelations($invoice);

    return $this->respondWithResource($invoice, $isStatusChanging
        ? "تم تغيير حالة الفاتورة إلى {$status}"
        : "تم تحديث الفاتورة بنجاح");
}

// دالة مساعدة للتحويل إلى رقم
protected function ensureNumeric($value): float
{
    return is_numeric($value) ? round(floatval($value), 2) : 0.00;
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
