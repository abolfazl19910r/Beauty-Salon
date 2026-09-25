<?php

namespace App\Http\Controllers\Admin\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\Payment\UnansweredVerifyRecovery;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * ⭐ پرداخت‌های نیازمند بررسی (۲۰۲۶-۰۹-۲۶) — جایی که خودکارسازی کنار کشید (پرچم needs_attention در payments:reconcile
 * یا Settlement ناموفق آسان پرداخت). فقط owner سالن (گروه salon.owner). PaymentTransaction عمداً scope سالن نداره،
 * پس همه‌ی پرس‌وجوها صریحاً با salon_id سالن فعلی فیلتر می‌شن.
 *
 * دو کار پس از بررسی پنل درگاه:
 * - «مبلغ گرفته شده → به کیف پول مشتری»: کل مبلغ نوبت به کیف پول (یا خود شارژ کیف پول) + پیامک، مثل بازیابی خودکار.
 * - «رسیدگی شد» با یادداشت: هیچ پولی جابه‌جا نمی‌شه (مثلاً درگاه خودش به کارت برگردونده بود).
 */
class AdminPaymentAttentionController extends Controller
{
    public function index(CurrentSalon $current): View
    {
        $transactions = PaymentTransaction::query()
            ->where('salon_id', $current->get()->id)
            ->where('needs_attention', true)
            ->with('user')
            ->latest('updated_at')
            ->paginate(20);

        return view('admin.payment-gateways.attention', compact('transactions'));
    }

    public function resolve(Request $request, CurrentSalon $current, int $transactionId, UnansweredVerifyRecovery $recovery): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:wallet_credit,resolved'],
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'note.required' => 'نتیجه‌ی بررسی پنل درگاه را در یادداشت بنویسید.',
        ]);

        $tx = PaymentTransaction::query()
            ->where('salon_id', $current->get()->id)
            ->where('needs_attention', true)
            ->findOrFail($transactionId);

        if ($data['action'] === 'wallet_credit') {
            if (! $recovery->creditManually($tx, $request->user(), $data['note'])) {
                return back()->with('error', 'این پرداخت قابل واریز به کیف پول نیست (یا همین الان رسیدگی شد).');
            }

            return back()->with('success', 'مبلغ به کیف پول مشتری واریز شد و پیامک ارسال شد.');
        }

        $response = (array) $tx->verify_response;
        $updated = PaymentTransaction::whereKey($tx->id)->where('needs_attention', true)->update([
            'needs_attention' => false,
            // وضعیت گیرکرده (reconciling / reversing) به failed برمی‌گرده تا دیگه هیچ مسیر خودکاری سراغش نره
            'status' => in_array($tx->status, ['reconciling', 'reversing'], true) ? 'failed' : $tx->status,
            'verify_response' => ['unanswered' => false] + $response + ['manual_resolution' => [
                'action' => 'resolved', 'by' => $request->user()->id, 'by_name' => $request->user()->name,
                'at' => now()->toIso8601String(), 'note' => $data['note'],
            ]],
        ]);

        Log::info('Payment needing attention marked resolved', ['transaction_id' => $tx->id, 'by' => $request->user()->id, 'applied' => $updated]);

        return back()->with('success', 'رسیدگی ثبت شد.');
    }
}
