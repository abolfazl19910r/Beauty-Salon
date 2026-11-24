<?php

namespace App\Support\Seo;

use App\Models\Salon;
use App\Support\CentralUrl;
use App\Support\PlatformSettings;
use Illuminate\Support\Str;

/**
 * داده‌ی ساختاریافته‌ی JSON-LD و متن‌های پیش‌فرض متا (سئو، ۲۰۲۶-۱۱-۲۳). فقط چیزهایی که خود سایت هم نشان می‌دهد.
 * امتیاز نظرها عمداً نیست: گوگل «نظر خود کسب‌وکار درباره‌ی خودش» را برای LocalBusiness نمی‌پذیرد.
 */
final class StructuredData
{
    /**
     * @param  int|null  $lowestPriceToman  ارزان‌ترین پلن قابل خرید (تومان) برای offers؛ schema.org کد ارز ISO 4217 می‌خواهد ← ریال (IRR)
     * @return array<int, array<string, mixed>>
     */
    public static function platform(?int $lowestPriceToman = null): array
    {
        $url = CentralUrl::to('/');
        $brand = (string) config('brand.name');

        $organization = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $url.'#organization',
            'name' => $brand,
            'alternateName' => 'Mahru',
            'url' => $url,
            'logo' => asset('logo-512.png'),
            'email' => self::setting('contact_email'),
            'telephone' => self::setting('support_phone'),
        ]);

        $website = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $url.'#website',
            'name' => $brand,
            'url' => $url,
            'inLanguage' => 'fa-IR',
            'publisher' => ['@id' => $url.'#organization'],
        ];

        $application = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $brand,
            'alternateName' => 'Mahru',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web, Android',
            'inLanguage' => 'fa-IR',
            'url' => $url,
            'description' => 'نرم‌افزار نوبت‌دهی آنلاین و مدیریت سالن‌های زیبایی',
            'publisher' => ['@id' => $url.'#organization'],
            'offers' => $lowestPriceToman > 0 ? [
                '@type' => 'AggregateOffer',
                'lowPrice' => (string) ($lowestPriceToman * 10),
                'priceCurrency' => 'IRR',
            ] : null,
        ]);

        return [$organization, $website, $application];
    }

    /** @return array<string, mixed> */
    public static function salon(Salon $salon): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BeautySalon',
            '@id' => route('home').'#salon',
            'name' => $salon->name,
            'url' => route('home'),
            'description' => self::salonDescription($salon),
            'image' => self::salonImage($salon),
            'telephone' => $salon->phone ?: null,
            'address' => $salon->address ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $salon->address,
                'addressCountry' => 'IR',
            ] : null,
            'foundingDate' => $salon->established_year ? (string) $salon->established_year : null,
        ]);
    }

    public static function salonDescription(Salon $salon): string
    {
        $text = trim((string) ($salon->tagline ?: $salon->bio));

        return $text !== ''
            ? self::clean($text)
            : 'رزرو آنلاین نوبت در '.$salon->name.' — خدمات، کارشناسان و ساعت‌های خالی را ببینید و نوبت بگیرید.';
    }

    public static function salonImage(Salon $salon): string
    {
        // همان لوگوی نمایشی سایت (بی امکان «لوگوی اختصاصی» پلن، لوگوی پیش‌فرض)
        return $salon->logoUrl() ?: asset('logo-512.png');
    }

    /** متن ساده‌ی یک‌خطی برای meta description (بی HTML، فاصله‌ی یکدست، حداکثر ~۱۶۰ نویسه). */
    public static function clean(?string $text, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return Str::limit($text, $limit, '…');
    }

    private static function setting(string $key): ?string
    {
        try {
            $value = trim((string) PlatformSettings::get($key));
        } catch (\Throwable) {
            return null;
        }

        return $value !== '' ? $value : null;
    }
}
