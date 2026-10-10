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
                'public_url' => $salon->publicUrl(),
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
