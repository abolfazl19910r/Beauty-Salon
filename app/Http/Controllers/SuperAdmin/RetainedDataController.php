<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DeletedAccountArchive;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Support\Retention;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «داده‌های نگهداری‌شده» (بسته‌ی مجوز 5د): پاسخ به درخواست مرجع قضایی (ماده‌ی ۶۶۹/۶۷۰ آیین دادرسی کیفری). جست‌وجو با شماره یا IP
 * در لاگ کارهای کلیدی (security_logs) و بایگانی حساب‌های حذف‌شده (رمزنگاری‌شده). فقط ابرمدیر؛ هر جست‌وجو در «گزارش کارهای
 * ابرمدیر» ثبت می‌شود.
 */
class RetainedDataController extends Controller
{
    public function index(Request $request, SuperAdminAudit $audit): View
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'ip' => ['nullable', 'ip'],
        ]);
        $phone = isset($data['phone']) ? preg_replace('/\D+/', '', (string) $data['phone']) : '';
        $ip = (string) ($data['ip'] ?? '');

        $archives = collect();
        $logs = null;

        if ($phone !== '' || $ip !== '') {
            $archives = DeletedAccountArchive::query()
                ->when($phone !== '', fn ($q) => $q->where('phone_hash', DeletedAccountArchive::hashPhone($phone)))
                ->latest('deleted_at')->limit(500)->get()
                // تاریخچه‌ی IP رمزنگاری‌شده است؛ جست‌وجوی IP روی همین چند صد ردیف رمزگشایی‌شده
                ->when($ip !== '', fn ($c) => $c->filter(fn (DeletedAccountArchive $a) => $a->deletion_ip === $ip
                    || collect($a->ip_history)->contains('ip', $ip)))
                ->values();

            $userIds = $phone !== ''
                ? User::query()->where('phone', $phone)->pluck('id')->merge($archives->pluck('user_id'))->unique()->all()
                : null;

            $logs = SecurityLog::query()->with(['user:id,name,phone', 'salon:id,name'])
                ->when($userIds !== null, fn ($q) => $q->whereIn('user_id', $userIds ?: [0]))
                ->when($ip !== '', fn ($q) => $q->where('ip_address', $ip))
                ->latest('created_at')->latest('id')
                ->paginate(50)->withQueryString();

            $audit->record('retained-data.search', 'جست‌وجوی داده‌ی نگهداری‌شده'
                .($phone !== '' ? ' — شماره '.$phone : '').($ip !== '' ? ' — IP '.$ip : ''));
        }

        return view('superadmin.retained-data.index', [
            'archives' => $archives,
            'logs' => $logs,
            'filters' => ['phone' => $phone, 'ip' => $ip],
            'retentionDays' => Retention::days('security_logs_days'),
            'archiveDays' => Retention::days('deleted_accounts_days'),
            'archiveCount' => DeletedAccountArchive::count(),
        ]);
    }
}
