<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformGatewayRequest;
use App\Models\PlatformSettlement;
use App\Models\Salon;
use App\Payments\PlatformGateway;
use App\Services\Payment\PlatformGateway\PlatformGatewayLedger;
use App\Services\Payment\PlatformGateway\PlatformGatewayService;
use App\Support\PayoutDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * «درگاه پلتفرم» در پنل ابرمدیر (۲۰۲۶-۱۱-۲۲): صف درخواست سالن‌ها (تأیید با درصد کارمزد یا رد با دلیل)، حساب جدای هر سالن
 * (کل دریافتی، سهم پلتفرم، سهم سالن، واریزشده، مانده) با فهرست تراکنش‌ها، و تسویه‌ها (ساخت، ثبت واریز با شماره‌ی پیگیری بانک، لغو،
 * ساخت برای همه، خروجی CSV برای واریز گروهی بانک). شماره‌ی کامل شبا/کارت/حساب فقط همین‌جا دیده می‌شود.
 */
class PlatformGatewayController extends Controller
{
    public function __construct(
        private readonly PlatformGatewayService $service,
        private readonly PlatformGatewayLedger $ledger,
    ) {}

    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status'), PlatformGatewayRequest::STATUSES) ? (string) $request->query('status') : 'pending';
        $summaries = $this->ledger->summaries();
        $salonIds = PlatformGatewayRequest::query()->whereIn('status', ['approved', 'revoked'])->distinct()->pluck('salon_id')
            ->merge(array_keys($summaries))->unique()->values();

        return view('superadmin.platform-gateway.index', [
            'status' => $status,
            'requests' => PlatformGatewayRequest::query()->with('salon')->where('status', $status)
                ->orderBy($status === 'pending' ? 'created_at' : 'reviewed_at', $status === 'pending' ? 'asc' : 'desc')
                ->paginate(30)->withQueryString(),
            'counts' => PlatformGatewayRequest::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
            'salons' => Salon::withoutGlobalScopes()->whereIn('id', $salonIds)->orderBy('name')->get(),
            'summaries' => $summaries,
            'active' => PlatformGatewayRequest::query()->where('status', 'approved')->pluck('fee_percent', 'salon_id')->all(),
            'totals' => $this->totals($summaries),
            'pendingSettlements' => PlatformSettlement::query()->where('status', 'pending')->count(),
            'enabled' => PlatformGateway::enabled(),
            'merchantReady' => \App\Support\PlatformZarinpal::merchantId() !== null,
        ]);
    }

    public function show(PlatformGatewayRequest $gatewayRequest): View
    {
        $gatewayRequest->load(['salon', 'requester', 'reviewer']);

        return view('superadmin.platform-gateway.request', [
            'req' => $gatewayRequest,
            'history' => PlatformGatewayRequest::query()->where('salon_id', $gatewayRequest->salon_id)->whereKeyNot($gatewayRequest->id)->latest('id')->get(),
            'maxFee' => PlatformGateway::maxFeePercent(),
        ]);
    }

    public function approve(Request $request, PlatformGatewayRequest $gatewayRequest): RedirectResponse
    {
        abort_unless($gatewayRequest->status === 'pending', 409);
        $fee = $this->feePercent($request);
        $note = $request->validate(['review_note' => ['nullable', 'string', 'max:500']])['review_note'] ?? null;

        $this->service->approve($gatewayRequest, $request->user(), $fee, $note);

        return redirect()->route('superadmin.platform-gateway.salon', $gatewayRequest->salon_id)
            ->with('success', "درگاه پلتفرم برای «{$gatewayRequest->salon->name}» تأیید شد.");
    }

    public function reject(Request $request, PlatformGatewayRequest $gatewayRequest): RedirectResponse
    {
        abort_unless($gatewayRequest->status === 'pending', 409);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'دلیل رد را بنویسید (برای مالک پیامک می‌شود).']);

        $this->service->reject($gatewayRequest, $request->user(), $data['reason']);

        return redirect()->route('superadmin.platform-gateway.index')->with('success', "درخواست «{$gatewayRequest->salon->name}» رد شد.");
    }

    public function salon(Request $request, int $salon): View
    {
        $salonModel = Salon::withoutGlobalScopes()->findOrFail($salon);
        $filter = in_array($request->query('tx'), ['unsettled', 'all'], true) ? $request->query('tx') : 'all';

        return view('superadmin.platform-gateway.salon', [
            'salon' => $salonModel,
            'active' => $this->service->activeFor($salonModel),
            'latest' => $this->service->latestFor($salonModel),
            'summary' => $this->ledger->summary($salonModel->id),
            'transactions' => $this->ledger->transactions($salonModel->id)
                ->when($filter === 'unsettled', fn ($q) => $q->whereIn('status', PlatformGateway::SETTLEABLE_STATUSES)->whereNull('platform_settlement_id'))
                ->latest('id')->paginate(50, ['*'], 'page')->withQueryString(),
            'filter' => $filter,
            'settlements' => PlatformSettlement::query()->where('salon_id', $salonModel->id)->latest('id')->get(),
            'maxFee' => PlatformGateway::maxFeePercent(),
        ]);
    }

    public function updateFee(Request $request, int $salon): RedirectResponse
    {
        $active = $this->service->activeFor(Salon::withoutGlobalScopes()->findOrFail($salon));
        abort_unless($active, 409);

        $this->service->changeFee($active, $request->user(), $this->feePercent($request));

        return back()->with('success', 'کارمزد تازه از پرداخت بعدی اعمال می‌شود؛ تراکنش‌های گذشته درصد خودشان را نگه می‌دارند.');
    }

    public function revoke(Request $request, int $salon): RedirectResponse
    {
        $active = $this->service->activeFor(Salon::withoutGlobalScopes()->findOrFail($salon));
        abort_unless($active, 409);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'دلیل لغو را بنویسید (برای مالک پیامک می‌شود).']);

        $this->service->revoke($active, $request->user(), $data['reason']);

        return back()->with('success', 'درگاه پلتفرم این سالن خاموش شد؛ مانده‌ی تسویه‌نشده همچنان قابل تسویه است.');
    }

    public function createSettlement(Request $request, int $salon): RedirectResponse
    {
        $salonModel = Salon::withoutGlobalScopes()->findOrFail($salon);
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        $settlement = $this->ledger->createSettlement($salonModel, $request->user(), note: $note);

        return back()->with($settlement ? 'success' : 'error', $settlement
            ? sprintf('تسویه‌ی #%s ساخته شد: %s تومان برای سالن. بعد از واریز، شماره‌ی پیگیری بانک را ثبت کنید.', to_persian_num((string) $settlement->id), to_persian_num(number_format(intdiv($settlement->salon_share_rial, 10))))
            : 'تراکنش تسویه‌نشده‌ای نیست (یا سالن مقصد واریز ندارد).');
    }

    public function settleAll(Request $request): RedirectResponse
    {
        $created = $this->ledger->createDue(1);

        return back()->with('success', sprintf('%s تسویه‌ی تازه ساخته شد.', to_persian_num((string) count($created))));
    }

    public function markPaid(Request $request, PlatformSettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->status === 'pending', 409);
        $data = $request->validate([
            'bank_reference' => ['required', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
        ], ['bank_reference.required' => 'شماره‌ی پیگیری واریز بانکی را بنویسید.']);

        $this->ledger->markPaid($settlement, $request->user(), trim($data['bank_reference']), isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : null);

        return back()->with('success', sprintf('تسویه‌ی #%s «واریز شد» ثبت شد.', to_persian_num((string) $settlement->id)));
    }

    public function cancelSettlement(Request $request, PlatformSettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->status === 'pending', 409);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:300']])['reason'] ?? null;

        $this->ledger->cancel($settlement, $request->user(), $reason);

        return back()->with('success', sprintf('تسویه‌ی #%s لغو شد و تراکنش‌هایش دوباره تسویه‌نشده‌اند.', to_persian_num((string) $settlement->id)));
    }

    /** تسویه‌های منتظر واریز با شماره‌ی کامل مقصد — برای واریز گروهی در اینترنت‌بانک (فایل حساس) */
    public function export(Request $request): StreamedResponse
    {
        app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('platform_gateway.export', 'خروجی CSV تسویه‌های منتظر واریز درگاه پلتفرم');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['settlement_id', 'salon_id', 'salon', 'account_holder', 'method', 'destination', 'bank', 'amount_toman', 'amount_rial', 'transactions', 'created_at']);
            PlatformSettlement::query()->with('salon')->where('status', 'pending')->orderBy('id')->each(function (PlatformSettlement $s) use ($out) {
                fputcsv($out, [
                    $s->id, $s->salon_id, $s->salon?->name, $s->account_holder, $s->payout_method, $s->payout_destination,
                    PlatformGatewayRequest::query()->where('salon_id', $s->salon_id)->latest('id')->value('payout_bank'),
                    intdiv($s->salon_share_rial, 10), $s->salon_share_rial, $s->transactions_count, $s->created_at?->toDateTimeString(),
                ]);
            });
            fclose($out);
        }, 'platform-settlements-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** @param  array<int, array<string, int>>  $summaries */
    private function totals(array $summaries): array
    {
        $totals = $this->ledger->empty();
        foreach ($summaries as $sum) {
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $sum[$key];
            }
        }

        return $totals;
    }

    /** درصد کارمزد از فرم (رقم فارسی و «٫» هم پذیرفته)، بین ۰ و سقف config */
    private function feePercent(Request $request): float
    {
        $raw = str_replace(['٫', ',', '،', '%', '٪', ' '], ['.', '.', '.', '', '', ''], PayoutDestination::digitsAndDot((string) $request->input('fee_percent')));
        $max = PlatformGateway::maxFeePercent();

        if ($raw === '' || ! is_numeric($raw) || (float) $raw < 0 || (float) $raw > $max || ! preg_match('/^\d+(\.\d{1,2})?$/', $raw)) {
            throw ValidationException::withMessages(['fee_percent' => sprintf('درصد کارمزد را بین ۰ و %s با حداکثر دو رقم اعشار بنویسید (مثلاً ۹ یا ۱۰٫۵).', to_persian_num((string) $max))]);
        }

        return round((float) $raw, 2);
    }
}
