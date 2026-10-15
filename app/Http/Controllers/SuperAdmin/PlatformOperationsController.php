<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\SmsCreditPurchase;
use App\Services\SuperAdmin\PlatformOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * ⭐ بخش‌هایی که داده‌شان پیاده شده بود ولی در پنل ابرمدیر دیده نمی‌شد (۲۰۲۶-۱۰-۱۵):
 * پیامک (مصرف همه‌ی سالن‌ها + خرید بسته‌ها و درآمدش)، پرداخت‌های پلتفرمِ نیازمند بررسی، کارهای ناموفق صف.
 */
class PlatformOperationsController extends Controller
{
    public function __construct(private readonly PlatformOverview $overview) {}

    public function sms(Request $request): View
    {
        $purchases = SmsCreditPurchase::query()
            ->with(['salon' => fn ($q) => $q->withoutGlobalScopes(), 'createdBy'])
            ->when(in_array($request->query('source'), ['online', 'grant'], true), fn ($q) => $q->where('source', $request->query('source')))
            ->when(in_array($request->query('status'), ['paid', 'pending', 'failed'], true), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $paid = SmsCreditPurchase::query()->where('status', 'paid');

        return view('superadmin.sms.index', [
            'usage' => $this->overview->smsUsage(),
            'purchases' => $purchases,
            'totals' => [
                'revenue_all' => (int) (clone $paid)->where('source', 'online')->sum('amount'),
                'revenue_month' => (int) (clone $paid)->where('source', 'online')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'parts_sold' => (int) (clone $paid)->where('source', 'online')->sum('parts'),
                'parts_granted' => (int) (clone $paid)->where('source', 'grant')->sum('parts'),
            ],
            'filters' => ['source' => $request->query('source'), 'status' => $request->query('status')],
        ]);
    }

    public function attention(): View
    {
        return view('superadmin.payment-attention.index', [
            'transactions' => $this->overview->attentionQuery()->with('user')->latest('updated_at')->paginate(20),
            'salonNames' => \App\Models\Salon::withoutGlobalScopes()->pluck('name', 'id'),
        ]);
    }

    /**
     * پرداخت اشتراک/بسته‌ی پیامک به درگاه خود پلتفرم رفته؛ اگر پول گرفته شده، ابرمدیر از «تمدید اشتراک» یا «افزودن اعتبار پیامک»
     * سالن آن را دستی اعمال می‌کند و اینجا فقط نتیجه را ثبت می‌کند (هیچ پولی خودکار جابه‌جا نمی‌شود).
     */
    public function resolveAttention(Request $request, int $transactionId): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ], ['note.required' => 'نتیجه‌ی بررسی پنل زرین‌پال را در یادداشت بنویسید.']);

        $tx = $this->overview->attentionQuery()->findOrFail($transactionId);
        $response = (array) $tx->verify_response;

        PaymentTransaction::whereKey($tx->id)->where('needs_attention', true)->update([
            'needs_attention' => false,
            'status' => in_array($tx->status, ['reconciling', 'reversing'], true) ? 'failed' : $tx->status,
            'verify_response' => ['unanswered' => false] + $response + ['manual_resolution' => [
                'action' => 'resolved', 'by' => $request->user()->id, 'by_name' => $request->user()->name,
                'at' => now()->toIso8601String(), 'note' => $data['note'], 'panel' => 'superadmin',
            ]],
        ]);

        app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('payment.resolved', sprintf('رسیدگی تراکنش #%d (%s): %s', $tx->id,
            $tx->purpose === 'subscription' ? 'اشتراک' : 'بسته‌ی پیامک', $data['note']), $tx->salon_id);
        Log::info('Platform payment needing attention marked resolved', ['transaction_id' => $tx->id, 'by' => $request->user()->id]);

        return back()->with('success', 'رسیدگی ثبت شد.');
    }

    public function failedJobs(): View
    {
        $jobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('failed_at')->paginate(20)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        $jobs->getCollection()->transform(function ($job) {
            $payload = json_decode((string) $job->payload, true) ?: [];
            $job->display_name = class_basename((string) ($payload['displayName'] ?? 'Job'));
            $job->error = trim(strtok((string) $job->exception, "\n") ?: '');

            return $job;
        });

        return view('superadmin.failed-jobs.index', compact('jobs'));
    }

    public function retryFailedJob(string $uuid): RedirectResponse
    {
        abort_unless(DB::table('failed_jobs')->where('uuid', $uuid)->exists(), 404);
        $name = $this->jobName($uuid);
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('queue.retried', "تلاش دوباره‌ی کار ناموفق {$name}");

        return back()->with('success', 'کار دوباره در صف قرار گرفت.');
    }

    public function forgetFailedJob(string $uuid): RedirectResponse
    {
        abort_unless(DB::table('failed_jobs')->where('uuid', $uuid)->exists(), 404);
        $name = $this->jobName($uuid);
        Artisan::call('queue:forget', ['id' => $uuid]);
        app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('queue.forgotten', "حذف کار ناموفق {$name}");

        return back()->with('success', 'از فهرست حذف شد.');
    }

    public function retryAllFailedJobs(): RedirectResponse
    {
        $count = DB::table('failed_jobs')->count();
        Artisan::call('queue:retry', ['id' => ['all']]);
        app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('queue.retried', "تلاش دوباره‌ی همه‌ی کارهای ناموفق ({$count})");

        return back()->with('success', 'همه‌ی کارهای ناموفق دوباره در صف قرار گرفتند.');
    }

    private function jobName(string $uuid): string
    {
        $payload = json_decode((string) DB::table('failed_jobs')->where('uuid', $uuid)->value('payload'), true) ?: [];

        return class_basename((string) ($payload['displayName'] ?? 'Job'));
    }
}
