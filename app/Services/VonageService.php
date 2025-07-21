<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Client\Exception\Exception as VonageException;
use Vonage\Messages\Channel\WhatsApp\WhatsAppText;

class VonageService
{
    protected $client;
    protected $adminNumbers = [];

    public function __construct()
    {
        $this->initializeClient();
        $this->loadAdminNumbers();
    }

    protected function initializeClient(): void
    {
        $apiKey = config('services.vonage.api_key');
        $apiSecret = config('services.vonage.api_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new \RuntimeException('Vonage API credentials are missing');
        }

        $this->client = new Client(
            new Basic($apiKey, $apiSecret)
        );
    }

    protected function loadAdminNumbers(): void
    {
        $numbers = config('services.vonage.admin_numbers', '');
        $this->adminNumbers = array_filter(
            explode(',', $numbers),
            fn($num) => $this->validatePhoneNumber($num)
        );
    }

    public function sendRejectionNotification(array $invoiceData): array
    {
        if (empty($this->adminNumbers)) {
            Log::error('No valid admin numbers configured');
            return ['success' => false, 'error' => 'No valid admin numbers'];
        }

        $message = $this->prepareRejectionMessage($invoiceData);
        $results = [];

        foreach ($this->adminNumbers as $number) {
            $results[$number] = $this->sendSingleMessage($number, $message);
        }

        return $results;
    }

    protected function prepareRejectionMessage(array $invoice): string
    {
        return sprintf(
            "🚨 *إشعار رفض فاتورة*\n\n".
            "📌 رقم الفاتورة: %s\n".
            "🏢 المكتب: %s\n".
            "👤 المسؤول: %s\n".
            "💵 المبلغ الإجمالي: %s ر.س\n".
            "📅 تاريخ الإنشاء: %s\n".
            "🛑 السبب: %s\n\n".
            "مع تحيات نظام إدارة الفواتير",
            $invoice['invoiceNumber'] ?? 'غير معروف',
            $invoice['office_name'] ?? 'غير معروف',
            $invoice['worker_name'] ?? 'غير معروف',
            $invoice['total'] ?? '0.00',
            $invoice['creationDateHijri'] ?? 'غير معروف',
            $invoice['reason'] ?? 'غير محدد'
        );
    }

    protected function sendSingleMessage(string $to, string $message): array
    {
        $defaultResponse = [
            'success' => false,
            'message_id' => null,
            'error' => null
        ];

        try {
            $fromNumber = config('services.vonage.from');
            
            if (empty($fromNumber)) {
                throw new \RuntimeException('Sender number is not configured');
            }

            $whatsAppMessage = new WhatsAppText($fromNumber, $to, $message);
            $response = $this->client->messages()->send($whatsAppMessage);
            
            if (is_object($response) && method_exists($response, 'getMessageId')) {
                Log::info('Invoice rejection notification sent', [
                    'to' => $to,
                    'message_id' => $response->getMessageId()
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->getMessageId()
                ];
            }

            throw new \RuntimeException('Invalid response from Vonage API');

        } catch (VonageException $e) {
            Log::error('Failed to send rejection notification', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return array_merge($defaultResponse, [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function validatePhoneNumber(string $number): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        return strlen($cleaned) >= 8 && strlen($cleaned) <= 15;
    }
}