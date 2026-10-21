<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\PushDevice;
use App\Models\Salon;
use App\Services\Push\AppMessenger;
use App\Services\Push\PushMode;
use App\Services\SuperAdmin\SuperAdminAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * «دستگاه‌ها»ی ابرمدیر (بسته‌ی ۷، ۲۰۲۶-۱۰-۲۲): هر گوشی‌ای که برای پوش ثبت شده، با وضعیت، نسخه‌ی اپ و ارسال‌های آخرش.
 * فقط ابرمدیر (تصمیم ۷-۲ الف). اقدام‌ها (تصمیم ۷-۱ الف): پوش آزمایشی بی‌پیامک، و «خروج این دستگاه» = ابطال توکن ورود اپ؛
 * دکمه‌ی «غیرفعال» عمداً نیست، چون اپ با هر بار باز شدن خودش را دوباره ثبت و فعال می‌کند. توکن FCM هرگز نمایش داده نمی‌شود.
 */
class PushDeviceController extends Controller
{
    public const STATUSES = [
        'active' => 'فعال',
        'never_acked' => 'هرگز رسید نداده',
        'disabled' => 'غیرفعال',
        'expired' => 'نشست منقضی',
    ];

    public const DISABLED_REASONS = [
        'invalid_token' => 'توکن باطل (اپ حذف یا داده‌اش پاک شد)',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'app' => in_array($request->query('app'), [PushDevice::APP_CUSTOMER, PushDevice::APP_STAFF], true) ? $request->query('app') : null,
            'status' => array_key_exists((string) $request->query('status'), self::STATUSES) ? (string) $request->query('status') : null,
            'salon' => $request->integer('salon') ?: null,
            'maker' => mb_substr(trim((string) $request->query('maker')), 0, 64) ?: null,
            'version' => mb_substr(trim((string) $request->query('version')), 0, 32) ?: null,
            'phone' => preg_replace('/\D/', '', strtr((string) $request->query('phone'), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'])) ?: null,
        ];

        $devices = $this->query($filters)
            ->with(['user:id,name,phone,user_type', 'salon:id,name,slug', 'accessToken:id,expires_at,last_used_at'])
            ->orderByDesc('push_devices.updated_at')->orderByDesc('push_devices.id')
            ->paginate(30)
            ->withQueryString();

        return view('superadmin.push.devices.index', [
            'devices' => $devices,
            'filters' => $filters,
            'salons' => Salon::withoutGlobalScopes()->orderBy('name')->pluck('name', 'id'),
            'makers' => PushDevice::query()->whereNotNull('manufacturer')->distinct()->orderBy('manufacturer')->pluck('manufacturer'),
            'versions' => $this->versions(),
            'mode' => PushMode::current(),
        ]);
    }

    public function show(PushDevice $device): View
    {
        $device->load(['user:id,name,phone,user_type', 'salon:id,name,slug', 'accessToken:id,expires_at,last_used_at,created_at']);

        $deliveries = $device->deliveries()
            ->with('message:id,event_key,title,urgency,mode,created_at,acked_at,sms_sent_at,sms_reason')
            ->orderByDesc('id')->limit(20)->get();

        return view('superadmin.push.devices.show', [
            'device' => $device,
            'status' => self::statusOf($device),
            'unhealthy' => $device->disabled_at === null && AppMessenger::isUnhealthy($device),
            'deliveries' => $deliveries,
            'latest' => AppRelease::latestFor($device->app),
            'mode' => PushMode::current(),
        ]);
    }

    public function test(PushDevice $device, AppMessenger $messenger): RedirectResponse
    {
        if (PushMode::current() === PushMode::OFF) {
            return back()->withErrors(['push' => 'حالت پوش پلتفرم «خاموش» است؛ پوش آزمایشی فرستاده نمی‌شود.']);
        }

        if (self::statusOf($device) !== 'active' && self::statusOf($device) !== 'never_acked') {
            return back()->withErrors(['push' => 'این دستگاه فعال نیست؛ پوش به آن نمی‌رسد.']);
        }

        $messenger->sendTest($device, 'پوش آزمایشی از پنل ابرمدیر. اگر این را می‌بینید، اعلان‌ها به این گوشی می‌رسد.');

        return back()->with('success', 'پوش آزمایشی صف شد. چند ثانیه بعد صفحه را تازه کنید تا پذیرش و رسید را ببینید.');
    }

    /** خروج اپ روی این گوشی: توکن ورود (Sanctum) حذف می‌شود و ردیف دستگاه با آن (cascade، و صریح برای اطمینان). */
    public function signOut(PushDevice $device, SuperAdminAudit $audit): RedirectResponse
    {
        $device->loadMissing(['user:id,name,phone', 'salon:id,name']);
        $label = sprintf('%s · %s %s', AppRelease::appName($device->app), $device->manufacturer ?? '—', $device->model ?? '');

        DB::transaction(function () use ($device) {
            $tokenId = $device->personal_access_token_id;
            $device->delete();
            DB::table('personal_access_tokens')->where('id', $tokenId)->delete();
        });

        $audit->record('push.device_signed_out', sprintf('خروج اپ «%s» روی گوشی %s', trim($label), $device->user?->phone ?? '—'), $device->salon_id);

        return redirect()->route('superadmin.push.devices.index')
            ->with('success', 'این گوشی از اپ خارج شد؛ برای ادامه باید دوباره وارد شود.');
    }

    public static function statusOf(PushDevice $device): string
    {
        if ($device->disabled_at !== null) {
            return 'disabled';
        }

        $expiresAt = $device->accessToken?->expires_at;
        if ($device->accessToken === null || ($expiresAt !== null && $expiresAt->isPast())) {
            return 'expired';
        }

        return $device->last_receipt_at === null ? 'never_acked' : 'active';
    }

    private function query(array $filters): Builder
    {
        $notExpired = fn ($q) => $q->whereNull('personal_access_tokens.expires_at')->orWhere('personal_access_tokens.expires_at', '>', now());

        return PushDevice::query()
            ->select('push_devices.*')
            ->leftJoin('personal_access_tokens', 'personal_access_tokens.id', '=', 'push_devices.personal_access_token_id')
            ->when($filters['app'], fn ($q, $app) => $q->where('push_devices.app', $app))
            ->when($filters['salon'], fn ($q, $salon) => $q->where('push_devices.salon_id', $salon))
            ->when($filters['maker'], fn ($q, $maker) => $q->where('push_devices.manufacturer', $maker))
            ->when($filters['version'], fn ($q, $version) => $q->where('push_devices.app_version', $version))
            ->when($filters['phone'], fn ($q, $phone) => $q->whereHas('user', fn ($u) => $u->where('phone', 'like', '%'.$phone.'%')))
            ->when($filters['status'], function ($q, $status) use ($notExpired) {
                match ($status) {
                    'disabled' => $q->whereNotNull('push_devices.disabled_at'),
                    'expired' => $q->whereNull('push_devices.disabled_at')
                        ->where(fn ($w) => $w->whereNull('personal_access_tokens.id')->orWhere('personal_access_tokens.expires_at', '<=', now())),
                    'never_acked' => $q->whereNull('push_devices.disabled_at')->whereNotNull('personal_access_tokens.id')->where($notExpired)
                        ->whereNull('push_devices.last_receipt_at'),
                    default => $q->whereNull('push_devices.disabled_at')->whereNotNull('personal_access_tokens.id')->where($notExpired)
                        ->whereNotNull('push_devices.last_receipt_at'),
                };
            });
    }

    /**
     * کارت «نسخه‌ی اپ روی گوشی‌ها»: برای هر اپ، شمار دستگاه‌های فعال هر نسخه و چند تا از آخرین نسخه‌ی منتشرشده عقب‌اند.
     *
     * @return array<string, array{latest: ?string, total: int, outdated: int, by_version: array<string, int>}>
     */
    private function versions(): array
    {
        $out = [];
        foreach (array_keys(AppRelease::VARIANTS) as $app) {
            $byVersion = PushDevice::query()->active()->where('app', $app)
                ->selectRaw("coalesce(app_version, '—') as v, count(*) as c")->groupBy('v')->orderByDesc('c')
                ->pluck('c', 'v')->map(fn ($c) => (int) $c)->all();

            $latest = AppRelease::latestFor($app)?->version_name;
            $outdated = $latest === null ? 0 : collect($byVersion)
                ->filter(fn ($c, $v) => $v === '—' || version_compare((string) $v, $latest, '<'))->sum();

            $out[$app] = ['latest' => $latest, 'total' => array_sum($byVersion), 'outdated' => (int) $outdated, 'by_version' => $byVersion];
        }

        return $out;
    }
}
