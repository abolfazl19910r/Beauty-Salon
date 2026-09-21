<?php

namespace App\Services;

use App\Models\Salon;
use App\Notifications\Sms\SmsQuotaExhaustedNotification;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Sms\SmsQuotaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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

    /**
     * ⭐ فیچر «سقف/قطع پیامک ماهانه» (تصمیم صریح ابوالفضل، ۲۰۲۶-۰۹-۲۰). $salonId اختیاری و در
     * آخر پارامترها است تا همه‌ی call siteهای قبلی (که salon context نمی‌فرستند) بدون تغییر کار
     * کنند — بدون salon_id، این متد رفتار قبلی‌اش را دارد و اصلاً وارد منطق سهمیه نمی‌شود. جاهایی
     * که واقعاً به سالن مشخصی وصل‌اند (پیامک‌های نوبت، یادآوری، ورود) باید صریحاً salon_id را پاس
     * بدهند تا واقعاً محافظت بشوند.
     */
    public function send(string $mobile, string $message, ?int $salonId = null): bool
    {
        if ($salonId !== null && ! $this->consumeQuotaOrNotify($salonId)) {
            return false;
        }

        // ⭐ لاگ متمرکز: تمام پیامک‌های پروژه (بوکینگ، مرخصی، برداشت وجه و...)
        // در نهایت از همین متد رد می‌شن (از طریق SmsChannel::send() → toSms())،
        // پس یک لاگ اینجا کافیه برای دیدن محتوای واقعی هر پیامک در فایل لاگ،
        // بدون نیاز به اتصال واقعی به Kavenegar.
        Log::info('SMS: در حال ارسال', ['mobile' => $mobile, 'message' => $message]);

        try {
            if (app()->environment(['local', 'testing']) && ! config('services.kavenegar.send_in_local', false)) {
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

    public function sendTemplate(string $mobile, string $templateName, array $tokens, ?int $salonId = null): bool
    {
        if ($salonId !== null && ! $this->consumeQuotaOrNotify($salonId)) {
            return false;
        }

        // ⭐ همین‌جا کد OTP (اولین token) هم قابل مشاهده‌ست — چون sendLoginCode/sendCode
        // هر دو نهایتاً از همین متد رد می‌شن.
        Log::info('SMS Template: در حال ارسال', [
            'mobile' => $mobile,
            'template' => $templateName,
            'tokens' => $tokens,
        ]);

        try {
            if (app()->environment(['local', 'testing']) && ! config('services.kavenegar.send_in_local', false)) {
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

    /**
     * ⭐ فیچر «سقف/قطع پیامک ماهانه». true = اجازه‌ی ارسال هست (و مصرف ثبت شد)؛ false = سهمیه
     * تمام شده، ارسال واقعی اصلاً نباید انجام بشه. سالن پیدا نشد → عمداً اجازه می‌دهد (fail-open)
     * تا یک salon_id نامعتبر/قدیمی هیچ پیامک واقعی‌ای را بی‌صدا قطع نکند؛ این حالت خودش در لاگ
     * ثبت می‌شود تا قابل پیگیری باشد.
     */
    private function consumeQuotaOrNotify(int $salonId): bool
    {
        $salon = app(SalonRepositoryInterface::class)->find($salonId);

        if (! $salon) {
            Log::warning('SmsQuotaService: salon not found for quota check, allowing send', ['salon_id' => $salonId]);

            return true;
        }

        $quota = app(SmsQuotaService::class);

        if (! $quota->hasQuotaRemaining($salon)) {
            if ($quota->shouldNotifyExhaustion($salon)) {
                $this->notifyQuotaExhausted($salon, $quota->quotaFor($salon));
            }

            Log::warning('SmsQuotaService: monthly SMS quota exhausted, send blocked', [
                'salon_id' => $salon->id,
                'quota' => $quota->quotaFor($salon),
            ]);

            return false;
        }

        $quota->recordUsage($salon);

        return true;
    }

    private function notifyQuotaExhausted(Salon $salon, int $quota): void
    {
        $recipients = $salon->admins()
            ->get()
            ->merge(app(UserRepositoryInterface::class)->getSuperAdmins())
            ->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SmsQuotaExhaustedNotification($salon, $quota));
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
