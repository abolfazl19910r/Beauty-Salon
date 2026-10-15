<?php

namespace App\Services\SuperAdmin;

use App\Models\Salon;
use App\Models\SuperAdminAuditLog;
use Illuminate\Support\Facades\Log;

/**
 * ثبت «گزارش کارهای ابرمدیر» (۲۰۲۶-۱۰-۱۵). خطای ثبت هرگز کار اصلی را نمی‌شکند (فقط لاگ).
 */
class SuperAdminAudit
{
    /** @param  array<string, mixed>|null  $changes */
    public function record(string $action, string $description, Salon|int|null $salon = null, ?array $changes = null): void
    {
        try {
            $user = auth()->user();
            $impersonator = session('impersonator_id');

            SuperAdminAuditLog::create([
                'user_id' => $impersonator ?: $user?->id,
                'user_name' => $impersonator ? \App\Models\User::find($impersonator)?->name : $user?->name,
                'action' => $action,
                'salon_id' => $salon instanceof Salon ? $salon->id : $salon,
                'description' => mb_substr($description, 0, 500),
                'changes' => $changes ?: null,
                'ip_address' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SuperAdminAudit: record failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    /**
     * فقط فیلدهایی که واقعاً عوض شده‌اند: ['field' => ['از' , 'به']]
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $out = [];
        foreach ($after as $key => $value) {
            $old = $before[$key] ?? null;
            if ((string) json_encode($old) !== (string) json_encode($value)) {
                $out[$key] = [$old, $value];
            }
        }

        return $out;
    }
}
