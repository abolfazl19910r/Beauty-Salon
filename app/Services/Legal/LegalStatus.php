<?php

namespace App\Services\Legal;

use App\Models\LegalAcceptance;
use App\Models\Salon;
use App\Models\User;

/**
 * کدام سندهای حقوقی را کاربر باید دوباره بپذیرد (پذیرش دوباره، ۲۰۲۶-۱۰-۲۸).
 *
 * سندی «در انتظار» است که آخرین نسخه‌ی «مهم» آن (config/legal.php ‏changes، major = true، تا نسخه‌ی فعلی) از آخرین نسخه‌ای که
 * کاربر پذیرفته تازه‌تر باشد. کاربری که هیچ ردیفی ندارد (پیش از legal_acceptances ثبت‌نام کرده) هم با اولین نسخه‌ی مهم در انتظار
 * می‌شود؛ تا وقتی هیچ نسخه‌ی مهمی نیست هیچ‌کس قفل نمی‌شود.
 *
 * قوانین سالن (salon_terms) قرارداد خود سالن است: پذیرش هر مالک برای همان سالن (salon_id) کافی است. بقیه‌ی سندها به خود کاربر
 * بسته‌اند (حساب مشتری در هر سالن جداست).
 */
class LegalStatus
{
    /** آخرین نسخه‌ی مهم یک سند تا نسخه‌ی فعلی؛ null یعنی هیچ تغییری پذیرش دوباره نمی‌خواهد */
    public function requiredVersion(string $document): ?string
    {
        $current = $this->currentVersion($document);
        $required = null;

        foreach ($this->changes($document) as $version => $change) {
            if (! empty($change['major']) && strcmp($version, $current) <= 0 && ($required === null || strcmp($version, $required) > 0)) {
                $required = $version;
            }
        }

        return $required;
    }

    public function currentVersion(string $document): string
    {
        return (string) config("legal.versions.{$document}");
    }

    public function acceptedVersion(User $user, string $document, ?Salon $salon = null): ?string
    {
        $query = LegalAcceptance::query()->where('document', $document);
        if ($document === 'salon_terms' && $salon) {
            $query->where('salon_id', $salon->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $versions = $query->distinct()->pluck('version')->map(fn ($v) => (string) $v)->all();
        usort($versions, 'strcmp');

        return $versions === [] ? null : end($versions);
    }

    /**
     * سندهای در انتظار پذیرش، با خلاصه‌ی تغییرهای بعد از نسخه‌ی پذیرفته‌شده.
     *
     * @param  list<string>  $documents
     * @return list<array{document: string, title: string, version: string, accepted_version: ?string, changes: list<array{version: string, major: bool, summary: string}>}>
     */
    public function pending(User $user, array $documents, ?Salon $salon = null): array
    {
        $pending = [];

        foreach ($documents as $document) {
            $required = $this->requiredVersion($document);
            if ($required === null) {
                continue;
            }

            $accepted = $this->acceptedVersion($user, $document, $salon);
            if ($accepted !== null && strcmp($accepted, $required) >= 0) {
                continue;
            }

            $current = $this->currentVersion($document);
            $changes = [];
            foreach ($this->changes($document) as $version => $change) {
                if (($accepted === null || strcmp($version, $accepted) > 0) && strcmp($version, $current) <= 0) {
                    $changes[] = ['version' => (string) $version, 'major' => ! empty($change['major']), 'summary' => (string) ($change['summary'] ?? '')];
                }
            }
            usort($changes, fn ($a, $b) => strcmp($b['version'], $a['version']));

            $pending[] = [
                'document' => $document,
                'title' => LegalAcceptance::DOCUMENTS[$document] ?? $document,
                'version' => $current,
                'accepted_version' => $accepted,
                'changes' => $changes,
            ];
        }

        return $pending;
    }

    /** آیا نسخه‌ی اپ صفحه‌ی «قوانین به‌روز شد» را دارد؟ اپ بی سرآیند نسخه هم قدیمی حساب می‌شود */
    public function appSupportsReacceptance(?string $appVersion): bool
    {
        if (! is_string($appVersion) || ! preg_match('/^\d+(\.\d+)*/', $appVersion, $m)) {
            return false;
        }

        return version_compare($m[0], (string) config('legal.reaccept_min_app_version'), '>=');
    }

    /** @return array<string, array{major?: bool, summary?: string}> */
    private function changes(string $document): array
    {
        return (array) config("legal.changes.{$document}", []);
    }
}
