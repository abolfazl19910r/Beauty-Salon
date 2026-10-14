<?php

namespace App\Services\Api;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;

/**
 * شکل «حساب» در پاسخ‌های ورود و GET /api/v1/me (بسته‌ی ۱ اپلیکیشن).
 */
class ApiAccountPresenter
{
    public static function present(User $user, string $audience, Salon $salon, ?Specialist $specialist = null): array
    {
        $account = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'type' => $audience,
            ],
            'salon' => [
                'id' => $salon->id,
                'slug' => $salon->slug,
                'name' => $salon->name,
                'logo_url' => $salon->logoUrl(),
                // ⭐ بسته‌ی د: اپ‌ها همان پلاک وب را می‌کشند (light = پلاک تیره روی تم روشن، dark = پلاک روشن روی تم تیره)
                'logo_tone' => $salon->logoUrl() ? $salon->logo_tone : null,
                'logo_dark_url' => $salon->logoDarkUrl(),
                'logo_dark_tone' => $salon->logoDarkUrl() ? $salon->logo_dark_tone : null,
                'public_url' => $salon->publicUrl(),
                'features' => $salon->enabledFeatureKeys(),
            ],
        ];

        if ($audience === ApiAudienceGate::STAFF && $specialist) {
            $account['specialist'] = [
                'id' => $specialist->id,
                'name' => $specialist->name,
            ];
        }

        return $account;
    }
}
