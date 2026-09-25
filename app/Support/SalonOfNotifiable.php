<?php

namespace App\Support;

use App\Models\Specialist;
use App\Models\User;

/**
 * سالنِ گیرنده‌ی یک اعلان، برای خواندن تنظیمات همان سالن در صف (جایی که CurrentSalon ست نیست).
 * مشتری و متخصص salon_id دارند؛ کارمند (ادمین/متخصص) salon_id=null دارد و از salon_admins یا متخصصِ لینک‌شده به او
 * پیدا می‌شود. نامعلوم: null.
 */
class SalonOfNotifiable
{
    public static function resolve(?object $notifiable): ?int
    {
        if ($notifiable instanceof Specialist) {
            return $notifiable->salon_id;
        }

        if (! $notifiable instanceof User) {
            return null;
        }

        if ($notifiable->user_type === 'customer' && $notifiable->salon_id) {
            return $notifiable->salon_id;
        }

        return $notifiable->salons()->value('salons.id')
            ?? Specialist::withoutGlobalScopes()
                ->where(fn ($q) => $q->where('user_id', $notifiable->id)->orWhere('phone', $notifiable->phone))
                ->value('salon_id')
            ?? $notifiable->salon_id;
    }
}
