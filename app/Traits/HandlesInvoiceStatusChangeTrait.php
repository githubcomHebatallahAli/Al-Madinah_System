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

        if ($invoice->invoiceStatus === $status) {
            $this->loadInvoiceRelations($invoice);
            return $this->respondWithResource($invoice, "Invoice is already set to $status");
        }

        $originalData = $invoice->getOriginal();

        $invoice->invoiceStatus = $status;

        if ($status === 'rejected') {
            $invoice->reason = $extra['reason'] ?? null;
        }

        if ($status === 'completed') {
            $invoice->payment_method_type_id = $extra['payment_method_type_id'];
            $invoice->paidAmount = $extra['paidAmount'];
            $invoice->discount = $extra['discount'] ?? 0;
            $invoice->tax = $extra['tax'] ?? 0;

            //    $invoice->calculateTotals(); // لضمان حساب المجموع النهائي بعد الخصم والضريبة

    if (round($invoice->paidAmount, 2) !== round($invoice->total, 2)) {
        return response()->json([
            'message' => 'لا يمكن اكتمال الفاتورة إلا إذا كان المبلغ المدفوع مساوياً لإجمالي الفاتورة.',
            'paidAmount' => $invoice->paidAmount,
            'total' => $invoice->total
        ], 422);
    }
        }

        $invoice->updated_by = $this->getUpdatedByIdOrFail();
        $invoice->updated_by_type = $this->getUpdatedByType();
        $invoice->save();

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

        if ($status === 'completed') {
            $invoice->updateSeatsCount();
            $invoice->calculateTotals();
            $invoice->updateIhramSuppliesCount();
        }

        $this->loadInvoiceRelations($invoice);
        return $this->respondWithResource($invoice, "Invoice set to $status");
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
