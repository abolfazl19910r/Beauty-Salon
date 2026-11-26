<?php

namespace App\Services\Admin\Dashboard;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Support\Features\Feature;

/**
 * کارت «راه‌اندازی سالن، X از ۵» داشبورد مدیر (بسته‌ی ۲ ظاهری، تصمیم ابوالفضل ۱۱-۲۴) — جای بنر قرمز درگاه و کارت بزرگ لینک/QR.
 * سه قدم اجباری + درگاه پرداخت «اختیاری» (بی درگاه رزرو بی‌پیش‌پرداخت است). بسته‌ی ۷ (تصمیم ابوالفضل ۱۱-۲۶): احراز سالن قدم جدا نیست؛
 * فقط پیش‌نیاز درگاه است و داخل همان قدم می‌آید.
 */
final class SalonSetupChecklist
{
    /**
     * بسته‌ی ۷ (آزمون production، ۲۰۲۶-۱۱-۲۶): هر قدم یک «چطور» کوتاه هم دارد تا مدیر تازه بداند کجا برود و چه پر کند.
     *
     * @return array{steps: list<array{key: string, label: string, hint: string, how: string, done: bool, optional: bool, url: ?string, cta: string}>, done: int, total: int, required_done: bool}
     */
    public static function for(Salon $salon): array
    {
        $hours = array_filter((array) ($salon->working_hours ?? []));
        $onlinePaymentInPlan = $salon->hasFeature(Feature::OnlinePayment);

        $steps = [
            [
                'key' => 'info',
                'label' => 'اطلاعات سالن',
                'hint' => 'نشانی، تلفن و ساعت کاری برای صفحه‌ی سالن و پیامک‌ها',
                'how' => 'نشانی و تلفن سالن را وارد کنید، تیک «نمایش ساعات کاری در سایت سالن» را بزنید، ساعت هر روز را تنظیم کنید و «ذخیره‌ی اطلاعات سالن» را بزنید.',
                'done' => filled($salon->address) && filled($salon->phone) && $hours !== [],
                'optional' => false,
                'url' => route('admin.salon-settings.edit'),
                'cta' => 'تکمیل اطلاعات سالن',
            ],
            [
                'key' => 'services',
                'label' => 'خدمات',
                'hint' => 'دست‌کم یک خدمت با قیمت و مدت',
                'how' => 'برای هر خدمت نام، قیمت و مدت را وارد کنید. دسته‌بندی اختیاری است و فقط خدمات را در سایت گروه‌بندی می‌کند.',
                'done' => BeautyService::query()->exists(),
                'optional' => false,
                'url' => route('admin.services.create'),
                'cta' => 'افزودن خدمت',
            ],
            [
                'key' => 'experts',
                'label' => 'کارشناس با ساعت کاری',
                'hint' => 'تا ساعت کاری کارشناس معلوم نباشد، نوبت خالی‌ای نشان داده نمی‌شود',
                'how' => '«افزودن کارشناس»: نام، موبایل و خدمت‌هایی که انجام می‌دهد؛ بعد در صفحه‌ی همان کارشناس «برنامه‌ی کاری هفتگی» روزها و ساعت‌ها را روشن کنید. اگر خودتان هم کار می‌کنید، با موبایل خودتان کارشناس شوید.',
                'done' => Specialist::query()->whereHas('schedules')->exists(),
                'optional' => false,
                'url' => route('admin.specialists.index'),
                'cta' => 'کارشناسان و ساعت کاری',
            ],
            [
                'key' => 'gateway',
                'label' => 'درگاه پرداخت',
                'hint' => $onlinePaymentInPlan
                    ? 'بدون درگاه، رزرو بدون پیش‌پرداخت انجام می‌شود و مبلغ کامل در سالن گرفته می‌شود.'
                    : 'پرداخت آنلاین در پلن سالن شما نیست؛ رزرو بدون پیش‌پرداخت انجام می‌شود.',
                'how' => ! $onlinePaymentInPlan ? '' : ($salon->isVerified()
                    ? 'دو راه دارید: «درگاه '.config('brand.name').'» بدون مرچنت (وقتی باز شد، با درخواست و تأیید) یا درگاه خود سالن با کد پذیرنده‌ی زرین‌پال.'
                    : ($salon->verification_status === 'pending'
                        ? 'مدارک احراز سالن در انتظار بررسی است؛ بعد از تأیید (پیامک می‌آید) درگاه را وصل کنید.'
                        : 'اول احراز سالن (کد ملی مالک و پروانه‌ی کسب)؛ بعد از تأیید، درگاه '.config('brand.name').' یا درگاه خود سالن را وصل کنید.')),
                'done' => $salon->acceptsOnlinePayments(),
                'optional' => true,
                'url' => ! $onlinePaymentInPlan ? route('admin.billing.index')
                    : ($salon->isVerified() ? route('admin.payment-gateways.index') : route('admin.verification.show')),
                'cta' => ! $onlinePaymentInPlan ? 'دیدن پلن‌ها'
                    : ($salon->isVerified() ? 'افزودن درگاه پرداخت' : ($salon->verification_status === 'pending' ? 'دیدن وضعیت احراز' : 'احراز سالن')),
            ],
        ];

        $done = count(array_filter($steps, fn ($s) => $s['done']));
        $requiredDone = collect($steps)->reject(fn ($s) => $s['optional'])->every(fn ($s) => $s['done']);

        return ['steps' => $steps, 'done' => $done, 'total' => count($steps), 'required_done' => $requiredDone];
    }
}
