<?php

namespace App\Services\Admin\Dashboard;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Support\Features\Feature;

/**
 * کارت «راه‌اندازی سالن، X از ۵» داشبورد مدیر (بسته‌ی ۲ ظاهری، تصمیم ابوالفضل ۱۱-۲۴) — جای بنر قرمز درگاه و کارت بزرگ لینک/QR.
 * چهار قدم اجباری + درگاه پرداخت «اختیاری» (بی درگاه رزرو بی‌پیش‌پرداخت است).
 */
final class SalonSetupChecklist
{
    /**
     * @return array{steps: list<array{key: string, label: string, hint: string, done: bool, optional: bool, url: ?string, cta: string}>, done: int, total: int, required_done: bool}
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
                'done' => filled($salon->address) && filled($salon->phone) && $hours !== [],
                'optional' => false,
                'url' => route('admin.salon-settings.edit'),
                'cta' => 'تکمیل اطلاعات سالن',
            ],
            [
                'key' => 'services',
                'label' => 'خدمات',
                'hint' => 'دست‌کم یک خدمت با قیمت و مدت',
                'done' => BeautyService::query()->exists(),
                'optional' => false,
                'url' => route('admin.services.create'),
                'cta' => 'افزودن خدمت',
            ],
            [
                'key' => 'experts',
                'label' => 'کارشناس با ساعت کاری',
                'hint' => 'تا ساعت کاری کارشناس معلوم نباشد، نوبت خالی‌ای نشان داده نمی‌شود',
                'done' => Specialist::query()->whereHas('schedules')->exists(),
                'optional' => false,
                'url' => route('admin.specialists.index'),
                'cta' => 'کارشناسان و ساعت کاری',
            ],
            [
                'key' => 'verification',
                'label' => 'احراز سالن',
                'hint' => 'مدارک سالن برای بررسی ماهرو',
                'done' => $salon->isVerified(),
                'optional' => false,
                'url' => route('admin.verification.show'),
                'cta' => 'ارسال مدارک',
            ],
            [
                'key' => 'gateway',
                'label' => 'درگاه پرداخت',
                'hint' => $onlinePaymentInPlan
                    ? 'بدون درگاه، رزرو بدون پیش‌پرداخت انجام می‌شود و مبلغ کامل در سالن گرفته می‌شود.'
                    : 'پرداخت آنلاین در پلن سالن شما نیست؛ رزرو بدون پیش‌پرداخت انجام می‌شود.',
                'done' => $salon->acceptsOnlinePayments(),
                'optional' => true,
                'url' => $onlinePaymentInPlan ? route('admin.payment-gateways.index') : route('admin.billing.index'),
                'cta' => $onlinePaymentInPlan ? 'افزودن درگاه پرداخت' : 'دیدن پلن‌ها',
            ],
        ];

        $done = count(array_filter($steps, fn ($s) => $s['done']));
        $requiredDone = collect($steps)->reject(fn ($s) => $s['optional'])->every(fn ($s) => $s['done']);

        return ['steps' => $steps, 'done' => $done, 'total' => count($steps), 'required_done' => $requiredDone];
    }
}
