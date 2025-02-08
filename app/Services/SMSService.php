<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SMSService
{
    protected mixed $apiKey;
    protected mixed $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.sms.api_key');
        $this->baseUrl = config('services.sms.base_url');
    }

    public function send($to, $message)
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post($this->baseUrl . '/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => $to,
                    'message' => $message
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('SMS sent successfully', ['phone' => $to]);
            return $result;

        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage(), [
                'phone' => $to,
                'message' => $message
            ]);
            return false;
        }
    }
}
