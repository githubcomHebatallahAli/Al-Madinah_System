<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function createCheckout()
    {
        $accessToken = env('HYPERPAY_AUTH_TOKEN');
        $entityId = env('HYPERPAY_VISA_MASTER_ENTITY_ID');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->asForm()->post('https://eu-test.oppwa.com/v1/checkouts', [
            'entityId' => $entityId,
            'amount' => '100.00',
            'currency' => 'SAR',
            'paymentType' => 'DB',
            'merchantTransactionId' => uniqid(),
            'testMode' => 'EXTERNAL',
            'merchantTransactionId' => uniqid('trx_'),
            'customer.email' => 'user@example.com',
        ]);

        return response()->json($response->json());
    }
}
