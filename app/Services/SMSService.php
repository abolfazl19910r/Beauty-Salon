<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SMSService
{
    protected mixed $apiKey;
    protected mixed $sender;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.kavenegar.api_key');
        $this->sender = config('services.kavenegar.sender', '10004346');
        $this->baseUrl = 'https://api.kavenegar.com/v1';
    }

    /**
     *
     * @param string $mobile
     * @param string $message
     * @return bool
     */
    public function send(string $mobile, string $message): bool
    {
        try {
            if (app()->environment('local') && !config('services.kavenegar.send_in_local', false)) {
                return true;
            }

            $response = Http::get("{$this->baseUrl}/{$this->apiKey}/sms/send.json", [
                'receptor' => $mobile,
                'message' => $message,
                'sender' => $this->sender
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['return']['status']) && $result['return']['status'] == 200) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param string $mobile
     * @param string $template
     * @param array $tokens
     * @return bool
     */
    public function sendTemplate(string $mobile, string $template, array $tokens): bool
    {
        try {
            if (app()->environment('local') && !config('services.kavenegar.send_in_local', false)) {
                return true;
            }

            $params = [
                'receptor' => $mobile,
                'template' => $template
            ];

            foreach ($tokens as $index => $token) {
                $params["token{$index}"] = $token;
            }

            $response = Http::get("{$this->baseUrl}/{$this->apiKey}/verify/lookup.json", $params);

            $result = $response->json();

            if ($response->successful() && isset($result['return']['status']) && $result['return']['status'] == 200) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param string $mobile
     * @param string $code
     * @return bool
     */
    public function sendVerificationCode(string $mobile, string $code): bool
    {
        return $this->sendTemplate($mobile, 'verify', [$code]);
    }

    /**
     *
     * @param string $mobile
     * @param array $data
     * @return bool
     */
    public function sendBookingConfirmation(string $mobile, array $data): bool
    {
        $message = sprintf(
            'نوبت شما در تاریخ %s ساعت %s با موفقیت ثبت شد. شماره پیگیری: %s',
            $data['date'],
            $data['time'],
            $data['reference']
        );

        return $this->send($mobile, $message);
    }

    /**
     *
     * @param string $mobile
     * @param array $data
     * @return bool
     */
    public function sendBookingReminder(string $mobile, array $data): bool
    {
        $message = sprintf(
            'یادآوری: نوبت شما در تاریخ %s ساعت %s. لطفا ۱۵ دقیقه قبل از نوبت حضور داشته باشید.',
            $data['date'],
            $data['time']
        );

        return $this->send($mobile, $message);
    }
}
