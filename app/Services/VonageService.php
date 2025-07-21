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

    public function __construct()
    {
        $this->initializeClient();
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

    public function sendWhatsAppMessage(string $to, string $message): array
    {
        $defaultResponse = [
            'success' => false,
            'message_id' => null,
            'response' => null,
            'error' => null,
            'code' => null
        ];

        if (!config('services.vonage.enabled', false)) {
            Log::warning('WhatsApp service is disabled in configuration');
            return array_merge($defaultResponse, [
                'error' => 'WhatsApp service is disabled'
            ]);
        }

        if (empty($to) || !$this->validatePhoneNumber($to)) {
            Log::error('Invalid recipient phone number', ['to' => $to]);
            return array_merge($defaultResponse, [
                'error' => 'Invalid recipient phone number'
            ]);
        }

        try {
            $fromNumber = config('services.vonage.from');
            
            if (empty($fromNumber)) {
                throw new \RuntimeException('Sender number is not configured');
            }

            $whatsAppMessage = new WhatsAppText(
                $fromNumber,
                $to,
                $message
            );

            $response = $this->client->messages()->send($whatsAppMessage);
            
            if (is_object($response) && method_exists($response, 'getMessageId')) {
                $messageId = $response->getMessageId();
                
                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'message_id' => $messageId,
                    'from' => $fromNumber
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'response' => $response->getResponseData(),
                    'error' => null,
                    'code' => null
                ];
            }

            throw new \RuntimeException('Invalid response from Vonage API');

        } catch (VonageException $e) {
            Log::error('Failed to send WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);

            return array_merge($defaultResponse, [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (\Throwable $e) {
            Log::critical('Unexpected error sending WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return array_merge($defaultResponse, [
                'error' => 'An unexpected error occurred',
                'code' => $e->getCode()
            ]);
        }
    }

    protected function validatePhoneNumber(string $number): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        return strlen($cleaned) >= 8 && strlen($cleaned) <= 15;
    }
}