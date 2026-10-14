<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Models\DiscountCode;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\UserNotification;
use App\Models\UserWalletTransaction;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Services\Customer\CustomerAccountDeletionService;
use App\Services\LoyaltyService;
use App\Services\PasswordStrengthService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Morilog\Jalali\Jalalian;

/**
 * کیف پول، باشگاه مشتریان، پروفایل، حذف حساب و صندوق اعلان مشتری در اپ «ماهرو» (بسته‌ی ۳).
 * شارژ کیف پول با درگاه: CustomerPaymentController::charge.
 */
class CustomerAccountController extends CustomerController
{
    // ── کیف پول ──────────────────────────────────────────────

    public function wallet(Request $request): JsonResponse
    {
        $wallet = $this->customer($request)->getOrCreateWallet();

        return ApiResponse::success([
            'balance' => (float) $wallet->balance,
            'total_deposited' => (float) $wallet->total_deposited,
            'total_spent' => (float) $wallet->total_spent,
        ]);
    }

    public function walletTransactions(Request $request): JsonResponse
    {
        $page = $this->customer($request)->getOrCreateWallet()->transactions()->orderByDesc('id')->paginate(20);

        return ApiResponse::success(collect($page->items())->map(fn (UserWalletTransaction $t) => [
            'id' => $t->id,
            'type' => $t->type,
            'amount' => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'description' => $t->description,
            'booking_id' => $t->booking_id,
            'created_at' => $t->created_at?->toIso8601String(),
            'created_at_jalali' => $t->created_at ? Jalalian::fromCarbon($t->created_at)->format('Y/m/d H:i') : null,
        ])->values(), $this->pageMeta($page));
    }

    // ── باشگاه مشتریان ─────────────────────────────────────────

    public function loyalty(Request $request, LoyaltyService $loyalty, RewardRepositoryInterface $rewards): JsonResponse
    {
        $user = $this->customer($request);
        $points = $loyalty->getCurrentPoints($user->id);
        $next = $rewards->getNextForPoints($points);

        return ApiResponse::success([
            'points' => $points,
            'expiring_points' => $loyalty->getExpiringPoints($user->id, 30),
            'next_reward' => $next ? [
                'id' => $next->id,
                'title' => $next->title,
                'points_needed' => $next->required_points - $points,
                'progress_percentage' => (int) min(100, $points / max(1, $next->required_points) * 100),
            ] : null,
            'rewards' => $loyalty->getAvailableRewards($user->id)->map(fn (Reward $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'description' => $r->description,
                'required_points' => (int) $r->required_points,
                'discount_type' => $r->discount_type,
                'discount_amount' => (float) $r->discount_amount,
                'can_redeem' => $r->isAvailableForUser($user),
            ])->values(),
        ]);
    }

    public function loyaltyHistory(Request $request, LoyaltyService $loyalty): JsonResponse
    {
        $page = $loyalty->getHistory($this->customer($request)->id, 20);

        return ApiResponse::success(collect($page->items())->map(fn (LoyaltyPoint $p) => [
            'id' => $p->id,
            'points' => (int) $p->points,
            'type' => $p->type,
            'description' => $p->description,
            'booking_id' => $p->booking_id,
            'expires_at' => $p->expires_at ? Carbon::parse($p->expires_at)->toIso8601String() : null,
            'created_at' => $p->created_at?->toIso8601String(),
            'created_at_jalali' => $p->created_at ? Jalalian::fromCarbon($p->created_at)->format('Y/m/d') : null,
        ])->values(), $this->pageMeta($page));
    }

    /** کدهای تخفیف شخصی مشتری (پاداش‌های گرفته‌شده) */
    public function discountCodes(Request $request, DiscountCodeRepositoryInterface $codes): JsonResponse
    {
        $present = fn (DiscountCode $c) => [
            'code' => $c->code,
            'type' => $c->type,
            'amount' => (float) $c->amount,
            'expires_at' => $c->expires_at ? Carbon::parse($c->expires_at)->toIso8601String() : null,
            'expires_at_jalali' => $c->expires_at ? Jalalian::fromCarbon(Carbon::parse($c->expires_at))->format('Y/m/d') : null,
            'remaining_uses' => max(0, (int) $c->max_uses - (int) $c->used_count),
        ];

        $userId = $this->customer($request)->id;

        return ApiResponse::success([
            'active' => $codes->getActiveForUser($userId)->map($present)->values(),
            'expired' => $codes->getExpiredForUser($userId)->map($present)->values(),
        ]);
    }

    public function redeem(Request $request, int $rewardId, LoyaltyService $loyalty): JsonResponse
    {
        $reward = Reward::query()->findOrFail($rewardId);

        try {
            $code = $loyalty->redeemReward($this->customer($request)->id, $reward);
        } catch (\Exception $e) {
            throw new ApiException('reward_not_available', 'امتیاز کافی ندارید یا این پاداش در دسترس نیست.', 409);
        }

        return ApiResponse::success([
            'code' => $code->code,
            'type' => $code->type,
            'amount' => (float) $code->amount,
            'expires_at' => $code->expires_at ? Carbon::parse($code->expires_at)->toIso8601String() : null,
            'points' => $loyalty->getCurrentPoints($this->customer($request)->id),
        ], [], 201);
    }

    // ── پروفایل و حساب ────────────────────────────────────────

    public function profile(Request $request): JsonResponse
    {
        $user = $this->customer($request);

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            // پیامک‌های پیگیری و پیشنهاد سالن (۲۰۲۶-۱۰-۱۴)؛ پیامک‌های نوبت جدا و همیشه روشن
            'marketing_sms' => $user->marketing_sms_opted_out_at === null,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /** نام و/یا marketing_sms (مثل وب؛ شماره هویت حساب است و عوض نمی‌شود). */
    public function updateProfile(Request $request): JsonResponse
    {
        $input = $request->validate([
            'name' => ['required_without:marketing_sms', 'string', 'max:255'],
            'marketing_sms' => ['sometimes', 'boolean'],
        ]);

        $user = $this->customer($request);
        if (array_key_exists('name', $input)) {
            $user->name = $input['name'];
        }
        if (array_key_exists('marketing_sms', $input)) {
            $user->marketing_sms_opted_out_at = $request->boolean('marketing_sms') ? null : ($user->marketing_sms_opted_out_at ?? now());
        }
        $user->save();

        return $this->profile($request);
    }

    /** رمز تازه همه‌ی توکن‌ها (از جمله همین دستگاه) را باطل می‌کند — اپ دوباره وارد می‌شود (بسته‌ی ۱). */
    public function updatePassword(Request $request, PasswordStrengthService $strength): JsonResponse
    {
        $input = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->customer($request);
        if (! Hash::check($input['current_password'], $user->password)) {
            throw new ApiException('invalid_password', 'رمز فعلی درست نیست.', 422);
        }

        $user->forceFill([
            'password' => Hash::make($input['password']),
            'password_changed_at' => now(),
            'password_strength_score' => $strength->score($input['password']),
        ])->save();

        return ApiResponse::success(['message' => 'رمز عبور تغییر کرد. دوباره وارد شوید.']);
    }

    /** DELETE account {password} — قاعده‌های CustomerAccountDeletionService (تصمیم ۲۰۲۶-۱۰-۱۱ «الف»). */
    public function destroy(Request $request, CustomerAccountDeletionService $deletion): JsonResponse
    {
        $input = $request->validate(['password' => ['required', 'string']]);

        $user = $this->customer($request);
        if (! Hash::check($input['password'], $user->password)) {
            throw new ApiException('invalid_password', 'رمز عبور درست نیست.', 422);
        }

        if ($blocker = $deletion->blocker($user)) {
            throw new ApiException('account_deletion_blocked', $blocker, 409);
        }

        $deletion->delete($user);

        return ApiResponse::success(['message' => 'حساب شما حذف شد.']);
    }

    // ── اعلان‌ها ──────────────────────────────────────────────

    public function notifications(Request $request): JsonResponse
    {
        $input = $request->validate(['unread' => ['nullable', 'boolean']]);

        $page = $this->notificationQuery($request)
            ->when(! empty($input['unread']), fn (Builder $q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(
            collect($page->items())->map(fn (UserNotification $n) => $this->presentNotification($n))->values(),
            $this->pageMeta($page) + ['unread_count' => $this->notificationQuery($request)->whereNull('read_at')->count()],
        );
    }

    public function readNotification(Request $request, string $notificationId): JsonResponse
    {
        $notification = $this->notificationQuery($request)->whereKey($notificationId)->first();
        if (! $notification) {
            throw new ApiException('not_found', 'اعلان پیدا نشد.', 404);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return ApiResponse::success($this->presentNotification($notification->fresh()));
    }

    public function readAllNotifications(Request $request): JsonResponse
    {
        return ApiResponse::success(['marked' => $this->notificationQuery($request)->whereNull('read_at')->update(['read_at' => now()])]);
    }

    private function notificationQuery(Request $request): Builder
    {
        $user = $this->customer($request);

        return UserNotification::limitToCurrentSalon(UserNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id));
    }

    private function presentNotification(UserNotification $n): array
    {
        $data = $n->data ?? [];

        return [
            'id' => $n->id,
            'type' => class_basename($n->type),
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? $data['description'] ?? 'اعلان جدید',
            'booking_id' => $data['booking_id'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String(),
            'created_at_jalali' => $n->created_at ? Jalalian::fromCarbon($n->created_at)->format('Y/m/d H:i') : null,
        ];
    }
}
