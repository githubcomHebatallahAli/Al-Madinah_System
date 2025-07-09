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

    // تطبيق جميع التغييرات المالية أولاً بغض النظر عن الحالة
    if (isset($extra['paidAmount'])) {
        $invoice->paidAmount = $this->ensureNumeric($extra['paidAmount']);
    }

    if (isset($extra['discount'])) {
        $invoice->discount = $this->ensureNumeric($extra['discount']);
    }

    if (isset($extra['tax'])) {
        $invoice->tax = $this->ensureNumeric($extra['tax']);
    }

    if (isset($extra['payment_method_type_id'])) {
        $invoice->payment_method_type_id = $extra['payment_method_type_id'];
    }

    // فقط إذا كانت الحالة تتغير فعلاً
    if ($invoice->invoiceStatus !== $status) {
        $invoice->invoiceStatus = $status;

        if ($status === 'rejected') {
            $invoice->reason = $extra['reason'] ?? null;
        }

        if ($status === 'completed') {
            $invoice->calculateTotals();

            if (round($invoice->paidAmount, 2) !== round($invoice->total, 2)) {
                return response()->json([
                    'message' => 'لا يمكن اكتمال الفاتورة إلا إذا كان المبلغ المدفوع مساوياً لإجمالي الفاتورة.',
                    'paidAmount' => number_format($invoice->paidAmount, 2),
                    'total' => number_format($invoice->total, 2)
                ], 422);
            }
        }
    }

    // تحديث التواريخ والمعلومات الإدارية
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();

    // حساب الإجماليات
    $invoice->calculateTotals();

    // بناء بيانات التغيير
    $changedData = $this->buildInvoiceChanges($invoice, $originalData);

    if (!empty($changedData)) {
        $invoice->changed_data = $changedData;
    }

   
    $invoice->save();

    $this->loadInvoiceRelations($invoice);

    return $this->respondWithResource($invoice, "Invoice updated successfully");
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
