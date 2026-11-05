<?php

namespace App\Services;

use App\Models\Salon;
use App\Notifications\Sms\SmsQuotaExhaustedNotification;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Sms\SmsQuotaService;
use App\Support\Features\Feature;
use App\Support\Logging\PersonalData;
use App\Support\Sms\OtpLog;
use App\Support\SmsParts;
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
        // KavenegarApi با کلید null خودش die() می‌کند (کل درخواست یا worker صف می‌میرد)؛ کلید نبودن در .env نباید برنامه را بکشد.
        $this->api = new KavenegarApi((string) config('services.kavenegar.api_key'));
        // ⭐ ۲۰۲۶-۱۱-۰۴: SDK به‌صورت پیش‌فرض گواهی TLS ‏api.kavenegar.com را بررسی نمی‌کند (config/services.php)
        $this->api->setSslVerify((bool) config('services.kavenegar.ssl_verify', true));
    }

    /**
     * ارسال واقعی به کاوه‌نگار انجام نشود؟ (فقط لاگ)
     * - SMS_LOG_ONLY=true (۲۰۲۶-۱۰-۲۴): در هر محیطی، حتی production — سرور واقعی پیش از آماده شدن کاوه‌نگار؛ کد ورود فقط در لاگ.
     *   بر KAVENEGAR_SEND_IN_LOCAL مقدم است.
     * - local/testing بدون KAVENEGAR_SEND_IN_LOCAL (رفتار قبلی).
     */
    protected function isLogOnly(): bool
    {
        return OtpLog::logOnlyMode();
    }

    /**
     * ⭐ فیچر «سقف/قطع پیامک ماهانه» (تصمیم صریح ابوالفضل، ۲۰۲۶-۰۹-۲۰). $salonId اختیاری و در
     * آخر پارامترها است تا همه‌ی call siteهای قبلی (که salon context نمی‌فرستند) بدون تغییر کار
     * کنند — بدون salon_id، این متد رفتار قبلی‌اش را دارد و اصلاً وارد منطق سهمیه نمی‌شود. جاهایی
     * که واقعاً به سالن مشخصی وصل‌اند (پیامک‌های نوبت، یادآوری، ورود) باید صریحاً salon_id را پاس
     * بدهند تا واقعاً محافظت بشوند.
     */
    public function send(string $mobile, string $message, ?int $salonId = null, ?Feature $feature = null): bool
    {
        // امکان پلن (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴): پیش از سهمیه — پیامکی که پلن سالن ندارد ساخته و شمرده نمی‌شود. فقط پیامک‌های
        // سالن که دسته‌شان را می‌گویند گیت می‌شوند؛ کد تأیید، هشدارهای پلتفرم و برگشت پول $feature ندارند و هرگز بسته نمی‌شوند.
        if ($salonId !== null && $feature !== null && ! $this->featureAllows($salonId, $feature)) {
            return false;
        }

        if ($salonId !== null && ! $this->consumeQuotaOrNotify($salonId, SmsParts::count($message))) {
            return false;
        }

        // ⭐ لاگ متمرکز: همه‌ی پیامک‌ها از همین‌جا رد می‌شوند. متن کامل فقط در حالت «فقط لاگ» (تنها راه دیدنش)؛ با ارسال واقعی
        // متن (نام مشتری، نشانی، لینک کوتاه با توکن) به laravel.log نمی‌رود و خط فقط debug است (بررسی لاگ‌ها ۲۰۲۶-۱۱-۰۵).
        if ($this->isLogOnly()) {
            Log::info('SMS: در حال ارسال', ['mobile' => PersonalData::phone($mobile), 'message' => $message]);
        } else {
            Log::debug('SMS: در حال ارسال', ['mobile' => PersonalData::phone($mobile), 'parts' => SmsParts::count($message), 'salon_id' => $salonId]);
        }

        try {
            if ($this->isLogOnly()) {
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
                'mobile' => PersonalData::phone($mobile),
                'code' => $e->getCode(),
            ]);
            $this->reportProviderProblem((int) $e->getCode(), $e->getMessage());

            return false;
        } catch (HttpException $e) {
            Log::error('Kavenegar HTTP Error (Send): '.$e->getMessage(), ['mobile' => PersonalData::phone($mobile)]);

            return false;
        } catch (\Exception $e) {
            Log::error('General SMS Send Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * پیامک احراز هویت (کد ورود، ۲FA، تأیید تلفن): تصمیم ۲۰۲۶-۰۹-۳۰ — خرج پلتفرم، هرگز با تمام شدن سهمیه‌ی سالن قطع
     * نمی‌شود و از سقف کم نمی‌شود؛ فقط جدا (otp_count) شمرده می‌شود تا مصرف غیرعادی یک سالن دیده شود.
     */
    public function sendAuthTemplate(string $mobile, string $templateName, array $tokens, ?int $salonId = null): bool
    {
        if ($salonId !== null && ($salon = app(SalonRepositoryInterface::class)->find($salonId))) {
            app(SmsQuotaService::class)->recordOtp($salon);
        }

        return $this->sendTemplate($mobile, $templateName, $tokens);
    }

    /**
     * هشدارهایی که نباید با تمام شدن سهمیه قطع شوند (پول گیر کرده؛ تصمیم ۲۰۲۶-۰۹-۳۰): از سهمیه‌ی سالن کم می‌شوند — حتی از سقف
     * بالاتر — ولی هرگز مسدود نمی‌شوند.
     */
    public function sendChargedWithoutLimit(string $mobile, string $message, int $salonId): bool
    {
        if ($salon = app(SalonRepositoryInterface::class)->find($salonId)) {
            app(SmsQuotaService::class)->recordUsage($salon, SmsParts::count($message));
        }

        return $this->send($mobile, $message);
    }

    public function sendTemplate(string $mobile, string $templateName, array $tokens, ?int $salonId = null): bool
    {
        if ($salonId !== null && ! $this->consumeQuotaOrNotify($salonId)) {
            return false;
        }

        // ⭐ همین‌جا کد OTP (اولین token) هم قابل مشاهده‌ست — چون sendLoginCode/sendCode
        // هر دو نهایتاً از همین متد رد می‌شن.
        // M2: کد (token اول) فقط در حالت «فقط لاگ» (info)؛ با ارسال واقعی *** و debug
        $context = ['mobile' => PersonalData::phone($mobile), 'template' => $templateName, 'tokens' => OtpLog::tokens($tokens)];
        $this->isLogOnly()
            ? Log::info('SMS Template: در حال ارسال', $context)
            : Log::debug('SMS Template: در حال ارسال', $context);

        try {
            if ($this->isLogOnly()) {
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
                'mobile' => PersonalData::phone($mobile),
                'template' => $templateName,
                'code' => $e->getCode(),
            ]);
            $this->reportProviderProblem((int) $e->getCode(), $e->getMessage(), $templateName);

            return false;

        } catch (HttpException $e) {
            Log::error('Kavenegar HTTP Error (Lookup): '.$e->getMessage(), [
                'mobile' => PersonalData::phone($mobile),
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
    /** آیا پلن سالن این دسته پیامک را دارد؟ خاموش = یک خط لاگ feature_blocked، بدون پیامک و بدون مصرف سهمیه */
    /**
     * ⭐ ۲۰۲۶-۱۱-۰۴: خطاهای سطح حساب کاوه‌نگار (جدول کدهای خطای مستندات REST) — تا رفع نشوند هیچ پیامکی (از جمله کد ورود) نمی‌رود؛ قبلاً
     * فقط در laravel.log بودند. اعلان داخلی به ابرمدیرها (فقط کانال database؛ نه پیامک که خودش از همین کاوه‌نگار می‌رود)، هر کد حداکثر یک
     * بار در ساعت. خطاهای یک پیامک خاص (گیرنده‌ی نامعتبر ۴۱۱، متن خالی ۴۱۳، …) و قطعی شبکه (HttpException) هشدار نمی‌دهند.
     */
    public const PROVIDER_ALERT_CODES = [
        401 => 'حساب کاربری کاوه‌نگار غیرفعال است',
        403 => 'کلید API کاوه‌نگار معتبر نیست (KAVENEGAR_API_KEY)',
        412 => 'خط فرستنده معتبر نیست (KAVENEGAR_SENDER)',
        416 => 'IP سرور در تنظیمات امنیتی کاوه‌نگار مجاز نیست',
        418 => 'اعتبار حساب کاوه‌نگار تمام شده است',
        420 => 'ارسال لینک در متن پیامک برای این حساب محدود شده است',
        424 => 'الگوی پیامک پیدا نشد یا هنوز تأیید نشده است',
        426 => 'ارسال کد با الگو (lookup) سرویس «پیشرفته» کاوه‌نگار می‌خواهد',
        427 => 'استفاده از این خط نیازمند سطح دسترسی در کاوه‌نگار است',
    ];

    public const PROVIDER_ALERT_MINUTES = 60;

    private function reportProviderProblem(int $code, string $message, ?string $template = null): void
    {
        if (! isset(self::PROVIDER_ALERT_CODES[$code])) {
            return;
        }

        try {
            if (! \Illuminate\Support\Facades\Cache::add('superadmin:kavenegar-alert:'.$code, true, now()->addMinutes(self::PROVIDER_ALERT_MINUTES))) {
                return;
            }

            app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send('sms_provider', sprintf(
                'پیامک ارسال نشد — %s (کد %d%s): %s',
                self::PROVIDER_ALERT_CODES[$code],
                $code,
                $template ? '، الگوی '.$template : '',
                \Illuminate\Support\Str::limit($message, 120)
            ), route('superadmin.sms.index', [], false));
        } catch (\Throwable $e) {
            Log::warning('Kavenegar provider alert failed', ['code' => $code, 'error' => $e->getMessage()]);
        }
    }

    public function featureAllows(int $salonId, Feature $feature): bool
    {
        if (Salon::featureEnabledFor($salonId, $feature)) {
            return true;
        }

        Log::info('SMS: feature_blocked', ['salon_id' => $salonId, 'feature' => $feature->value]);

        return false;
    }

    private function consumeQuotaOrNotify(int $salonId, int $parts = 1): bool
    {
        $salon = app(SalonRepositoryInterface::class)->find($salonId);

        if (! $salon) {
            Log::warning('SmsQuotaService: salon not found for quota check, allowing send', ['salon_id' => $salonId]);

            return true;
        }

        $quota = app(SmsQuotaService::class);

        if (! $quota->consume($salon, $parts)) {
            if ($quota->shouldNotifyExhaustion($salon)) {
                $this->notifyQuotaExhausted($salon, $quota->quotaFor($salon));
            }

            Log::warning('SmsQuotaService: monthly SMS quota exhausted, send blocked', [
                'salon_id' => $salon->id,
                'quota' => $quota->quotaFor($salon),
                'parts' => $parts,
            ]);

            return false;
        }

        if ($quota->shouldWarnNearLimit($salon)) {
            $this->notifyQuotaNearLimit($salon, $quota->quotaFor($salon), $quota->credit($salon));
        }

        return true;
    }

    /** هشدار ۸۰٪ فقط به مدیران خود سالن (۲۰۲۶-۰۹-۳۰) — تا قبل از قطع شدن فرصت خرید بسته داشته باشند */
    private function notifyQuotaNearLimit(Salon $salon, int $quota, int $credit): void
    {
        $admins = $salon->admins()->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new \App\Notifications\Sms\SmsQuotaWarningNotification($salon, $quota, $credit));
        }
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
