<?php

namespace App\Services;

use Vonage\Client;
use Vonage\Client\Exception\Exception as VonageException;
use Vonage\Messages\Channel\WhatsApp\WhatsAppText;

class VonageService
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function sendWhatsAppMessage(string $to, string $message): array
    {
        try {
            $whatsAppMessage = new WhatsAppText(
                config('services.vonage.from'),
                $to,
                $message
            );

            $response = $this->client->messages()->send($whatsAppMessage);

            return [
                'success' => true,
                'response' => $response
            ];
        } catch (VonageException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}