<?php

namespace App\Traits;

use App\Models\PaymentMethodType;


trait HandlesInvoiceStatusChangeTrait
{


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

    public function changeInvoiceStatus($invoice, string $status, array $extra = []): \Illuminate\Http\JsonResponse
{
    $this->authorize('manage_system');

    if (!$invoice) {
        return response()->json(['message' => "Invoice not found."], 404);
    }

    if ($invoice->invoiceStatus === $status) {
        $this->loadInvoiceRelations($invoice);
        return $this->respondWithResource($invoice, "Invoice is already set to $status");
    }

    $originalData = $invoice->getOriginal();

    // نجهز القيم التي سيتم تغييرها
    $invoice->invoiceStatus = $status;

    if ($status === 'rejected') {
        $invoice->reason = $extra['reason'] ?? null;
    }

    if ($status === 'completed') {
        $tempPaidAmount = $this->ensureNumeric($extra['paidAmount']);
        $tempDiscount = $this->ensureNumeric($extra['discount'] ?? 0);
        $tempTax = $this->ensureNumeric($extra['tax'] ?? 0);

        // نحسب total مؤقتًا بدون حفظ
        $tempInvoice = clone $invoice;
        $tempInvoice->discount = $tempDiscount;
        $tempInvoice->tax = $tempTax;
        $tempInvoice->calculateTotals();

        if (round($tempPaidAmount, 2) !== round($tempInvoice->total, 2)) {
            return response()->json([
                'message' => 'لا يمكن اكتمال الفاتورة إلا إذا كان المبلغ المدفوع مساوياً لإجمالي الفاتورة.',
                'paidAmount' => number_format($tempPaidAmount, 2),
                'total' => number_format($tempInvoice->total, 2)
            ], 422);
        }

        // ✅ التعديلات الفعلية بعد التأكد من صحة القيم
        $invoice->paidAmount = $tempPaidAmount;
        $invoice->discount = $tempDiscount;
        $invoice->tax = $tempTax;
        $invoice->payment_method_type_id = $extra['payment_method_type_id'];

        // حساب القيم المرتبطة
        $invoice->updateSeatsCount();
        $invoice->calculateTotals();
        $invoice->updateIhramSuppliesCount();
    }

    // بيانات التعديل
    $invoice->updated_by = $this->getUpdatedByIdOrFail();
    $invoice->updated_by_type = $this->getUpdatedByType();
    $invoice->save();

    // تتبع التعديلات
    $changedData = $this->buildInvoiceChanges($invoice, $originalData);

    if ($status === 'completed' && $invoice->isDirty('payment_method_type_id')) {
        $newPaymentMethod = PaymentMethodType::with('paymentMethod')->find($extra['payment_method_type_id']);
        $changedData['payment_method'] = [
            'old' => [
                'type' => $invoice->paymentMethodType?->type,
                'by' => $invoice->paymentMethodType?->by,
                'method' => $invoice->paymentMethodType?->paymentMethod?->name,
            ],
            'new' => $newPaymentMethod ? [
                'type' => $newPaymentMethod->type,
                'by' => $newPaymentMethod->by,
                'method' => $newPaymentMethod->paymentMethod?->name,
            ] : null
        ];
    }

    if (!empty($changedData)) {
        $invoice->changed_data = $changedData;
        $invoice->save();
    }

    $this->loadInvoiceRelations($invoice);

    return $this->respondWithResource($invoice, "Invoice set to $status");
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
