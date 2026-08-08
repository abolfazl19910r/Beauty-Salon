<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Kavenegar\KavenegarApi;

class SMSService
{
    protected $api;

    public function __construct()
    {
        $this->api = new KavenegarApi(config('services.kavenegar.api_key'));
    }

    public function send(string $mobile, string $message): bool
    {
        // ⭐ لاگ متمرکز: تمام پیامک‌های پروژه (بوکینگ، مرخصی، برداشت وجه و...)
        // در نهایت از همین متد رد می‌شن (از طریق SmsChannel::send() → toSms())،
        // پس یک لاگ اینجا کافیه برای دیدن محتوای واقعی هر پیامک در فایل لاگ،
        // بدون نیاز به اتصال واقعی به Kavenegar.
        Log::info('SMS: در حال ارسال', ['mobile' => $mobile, 'message' => $message]);

        try {
            if (app()->environment('local') && ! config('services.kavenegar.send_in_local', false)) {
                return true;
            }

            $result = $this->api->Send(
                config('services.kavenegar.sender'),
                $mobile,
                $message
            );

            return true;

        } catch (ApiException $e) {
            Log::error('Kavenegar API Error (Send): '.$e->getMessage(), [
                'mobile' => $mobile,
                'code' => $e->getCode(),
            ]);

            return false;
        } catch (HttpException $e) {
            Log::error('Kavenegar HTTP Error (Send): '.$e->getMessage(), ['mobile' => $mobile]);

            return false;
        } catch (\Exception $e) {
            Log::error('General SMS Send Error: '.$e->getMessage());

            return false;
        }
    }

    public function sendTemplate(string $mobile, string $templateName, array $tokens): bool
    {
        // ⭐ همین‌جا کد OTP (اولین token) هم قابل مشاهده‌ست — چون sendLoginCode/sendCode
        // هر دو نهایتاً از همین متد رد می‌شن.
        Log::info('SMS Template: در حال ارسال', [
            'mobile' => $mobile,
            'template' => $templateName,
            'tokens' => $tokens,
        ]);

        try {
            if (app()->environment('local') && ! config('services.kavenegar.send_in_local', false)) {
                return true;
            }

            $token1 = $tokens[0] ?? null;
            $token2 = $tokens[1] ?? null;
            $token3 = $tokens[2] ?? null;

            $result = $this->api->VerifyLookup(
                $mobile,
                $token1,
                $token2,
                $token3,
                $templateName,
                'sms'
            );

            return true;

        } catch (ApiException $e) {
            Log::error('Kavenegar API Error (Lookup): '.$e->getMessage(), [
                'mobile' => $mobile,
                'template' => $templateName,
                'code' => $e->getCode(),
            ]);

            return false;

        } catch (HttpException $e) {
            Log::error('Kavenegar HTTP Error (Lookup): '.$e->getMessage(), [
                'mobile' => $mobile,
                'template' => $templateName,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('General SMS Lookup Error: '.$e->getMessage());

            return false;
        }
    }

    //    public function sendVerificationCode(string $mobile, string $code, string $type = 'login'): bool
    //    {
    //        $templateMap = [
    //            'login'    => config('services.kavenegar.templates.login_verify'),
    //            'register' => config('services.kavenegar.templates.register_verify'),
    //            'reset'    => config('services.kavenegar.templates.reset_password'),
    //            '2fa'      => config('services.kavenegar.templates.two_factor_auth'),
    //        ];
    //
    //        $template = $templateMap[$type] ?? config('services.kavenegar.templates.login_verify');
    //
    //        return $this->sendTemplate($mobile, $template, [$code]);
    //    }

    public function sendVerificationCode(string $mobile, string $code, string $type = 'login'): bool
    {
        $messages = [
            'login' => "سالن زیبایی\nکد تایید ورود شما: {$code}\nاین کد تا ۲ دقیقه اعتبار دارد.\nلغو۱۱",
            'register' => "سالن زیبایی\nکد تایید ثبت‌نام: {$code}\nاین کد تا ۲ دقیقه اعتبار دارد.\nلغو۱۱",
            'reset' => "سالن زیبایی\nکد بازیابی رمز عبور: {$code}\nاین کد تا ۵ دقیقه اعتبار دارد.\nلغو۱۱",
            '2fa' => "سالن زیبایی\nکد احراز هویت دو مرحله‌ای: {$code}\nاین کد تا ۲ دقیقه اعتبار دارد.\nلغو۱۱",
        ];

        $message = $messages[$type] ?? $messages['login'];

        return $this->send($mobile, $message);
    }

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

    public function sendBookingReminder(string $mobile, array $data): bool
    {
        $message = sprintf(
            'یادآوری: نوبت شما در تاریخ %s ساعت %s. لطفا 15 دقیقه قبل از نوبت حضور داشته باشید.',
            $data['date'],
            $data['time']
        );

        return $this->send($mobile, $message);
    }
}
