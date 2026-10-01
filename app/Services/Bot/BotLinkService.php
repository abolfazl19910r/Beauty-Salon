<?php

namespace App\Services\Bot;

use App\Models\BotLink;
use App\Models\BotLinkCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * اتصال گفت‌وگوی بله/تلگرام به حساب کاربر با کد یک‌بارمصرف (۱۰ دقیقه، فقط hash ذخیره می‌شود).
 */
class BotLinkService
{
    public const CODE_TTL_MINUTES = 10;

    /** کد تازه برای کاربر؛ کدهای استفاده‌نشده‌ی قبلی همان کاربر باطل می‌شوند. */
    public function createCode(User $user, ?int $salonId): string
    {
        $code = Str::upper(Str::random(10));

        DB::transaction(function () use ($user, $salonId, $code) {
            BotLinkCode::where('user_id', $user->id)->whereNull('used_at')->delete();
            BotLinkCode::create([
                'user_id' => $user->id, 'salon_id' => $salonId, 'code_hash' => self::hash($code),
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            ]);
        });

        return $code;
    }

    /** null یعنی کد نامعتبر/منقضی/مصرف‌شده. */
    public function consume(string $messenger, string $chatId, string $code): ?BotLink
    {
        return DB::transaction(function () use ($messenger, $chatId, $code) {
            $row = BotLinkCode::where('code_hash', self::hash(Str::upper(trim($code))))->lockForUpdate()->first();

            if (! $row || $row->used_at || $row->expires_at->isPast()) {
                return null;
            }

            $row->update(['used_at' => now()]);

            // یک گفت‌وگو فقط مال یک کاربر، و هر کاربر در هر پیام‌رسان یک گفت‌وگو
            BotLink::where('messenger', $messenger)->where('chat_id', $chatId)->where('user_id', '!=', $row->user_id)->delete();

            return BotLink::updateOrCreate(
                ['user_id' => $row->user_id, 'messenger' => $messenger],
                ['chat_id' => $chatId, 'salon_id' => $row->salon_id],
            );
        });
    }

    public function unlinkUser(User $user, string $messenger): bool
    {
        return BotLink::where('user_id', $user->id)->where('messenger', $messenger)->delete() > 0;
    }

    public function unlinkChat(string $messenger, string $chatId): bool
    {
        return BotLink::where('messenger', $messenger)->where('chat_id', $chatId)->delete() > 0;
    }

    private static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
