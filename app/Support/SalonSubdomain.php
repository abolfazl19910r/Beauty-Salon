<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * آدرس اختصاصی سالن (slug) — هم مسیر /s/{slug} و هم ساب‌دامین {slug}.دامنه (بسته‌ی Docker، ۲۰۲۶-۱۰-۲۳).
 *
 * چون slug برچسب DNS هم هست: فقط حروف کوچک انگلیسی، عدد و خط تیره؛ اول و آخر حرف یا عدد؛ حداکثر ۶۳ کاراکتر (سقف هر برچسب DNS).
 * قبلاً alpha_dash بود که زیرخط (در نام میزبان نامعتبر) و حروف یونیکد/فارسی را هم می‌پذیرفت و سقفش ۱۰۰ بود. نام‌های زیرساخت
 * (www، admin، api، ...) رزروند تا هیچ سالنی ساب‌دامین آن‌ها را نگیرد. ورودی پیش از اعتبارسنجی کوچک و بی‌فاصله می‌شود.
 */
final class SalonSubdomain
{
    public const PATTERN = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/';

    public const MAX = 63;

    /** @var list<string> */
    public const RESERVED = [
        'www', 'admin', 'superadmin', 'api', 'app', 'apps', 'mail', 'email', 'smtp', 'imap', 'pop', 'ftp', 'ns', 'ns1', 'ns2',
        'static', 'assets', 'build', 'storage', 'cdn', 'media', 'img', 'status', 'pma', 'phpmyadmin', 'telescope', 'horizon',
        'login', 'logout', 'register', 'signup', 'salon-signup', 'dashboard', 'panel', 'support', 'help', 'blog', 'docs',
        'tg', 'bot', 'webhook', 'ntfy', 'push', 'pay', 'payment', 'payments', 'billing', 'test', 'demo-admin', 'localhost',
    ];

    public static function normalize(mixed $value): mixed
    {
        return is_string($value) ? mb_strtolower(trim($value)) : $value;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED, true);
    }

    /** @return list<mixed> قواعد اعتبارسنجی slug سالن تازه */
    public static function rules(): array
    {
        return [
            'required', 'string', 'max:'.self::MAX, 'regex:'.self::PATTERN,
            Rule::notIn(self::RESERVED),
            Rule::unique('salons', 'slug'),
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'slug.unique' => 'این آدرس قبلاً برای سالن دیگری استفاده شده است.',
            'slug.regex' => 'آدرس فقط حروف کوچک انگلیسی، عدد و خط تیره (-) می‌پذیرد و باید با حرف یا عدد شروع و تمام شود.',
            'slug.max' => 'آدرس حداکثر ۶۳ کاراکتر است.',
            'slug.not_in' => 'این آدرس رزرو شده است؛ آدرس دیگری انتخاب کنید.',
        ];
    }
}
