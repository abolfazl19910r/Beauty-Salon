<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Security\CheckPasswordStrengthRequest;
use App\Models\SecurityLog;
use App\Models\SecuritySetting;
use App\Services\SecurityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(protected readonly SecurityLogService $securityLogService) {}

    public function dashboard(): View
    {
        $user = auth()->user();

        return view('security.dashboard', [
            'two_factor_enabled' => $user->two_factor_enabled,
            'active_sessions_count' => $this->getActiveSessions()->count(),
            'recent_activities' => $this->getRecentActivities(),
            'security_score' => $this->calculateSecurityScore(),
            'last_password_change' => $user->password_changed_at,
            'login_attempts_today' => $this->getLoginAttempts(),
        ]);
    }

    public function sessions(): View
    {
        return view('security.sessions', [
            'sessions' => $this->getActiveSessions(),
        ]);
    }

    public function activity(): View
    {
        $logs = SecurityLog::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('security.activity', compact('logs'));
    }

    public function getActiveSessions(): Collection
    {
        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->select(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $session->last_activity = Carbon::createFromTimestamp($session->last_activity);
                $session->is_current_device = $session->id === session()->getId();

                return $session;
            });
    }

    /**
     * ⭐ Fix (test-writing session 8): the route parameter used to be named {id}, which
     * fell under the global `Route::pattern('id', '[0-9a-f-]+')` constraint in
     * RouteServiceProvider (designed for lowercase-hex UUIDs like notification ids).
     * Laravel's real session IDs are `Str::random(40)` — mixed-case alphanumeric — so
     * virtually every real session ID contains an uppercase letter or a non-hex lowercase
     * letter (g-z) and would never match that pattern. The route 404'd for essentially
     * every real session, meaning "end this session" never actually worked. Renaming the
     * parameter to {sessionId} takes it out from under the global `id` pattern.
     */
    public function terminateSession(string $sessionId): JsonResponse
    {
        if ($sessionId === session()->getId()) {
            return response()->json([
                'error' => 'نمی‌توانید نشست فعلی را پایان دهید.',
            ], 422);
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->delete();

        $this->securityLogService->logSuspiciousActivity('session_terminated', [
            'session_id' => $sessionId,
        ]);

        return response()->json([
            'message' => 'نشست با موفقیت پایان یافت.',
        ]);
    }

    public function terminateAllSessions(): JsonResponse
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->securityLogService->logSuspiciousActivity('all_sessions_terminated');

        return response()->json([
            'message' => 'تمام نشست‌های دیگر با موفقیت پایان یافتند.',
        ]);
    }

    public function getSecurityLogs(Request $request): JsonResponse
    {
        $logs = SecurityLog::where('user_id', auth()->id())
            ->when($request->type, fn ($query, $type) => $query->where('event', $type))
            ->when($request->date_from, fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date)))
            ->when($request->date_to, fn ($query, $date) => $query->where('created_at', '<=', Carbon::parse($date)))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($logs);
    }

    public function getLoginHistory(): JsonResponse
    {
        $history = SecurityLog::where('user_id', auth()->id())
            ->where('event', 'login_attempt')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return response()->json($history);
    }

    public function checkPasswordStrength(CheckPasswordStrengthRequest $request): JsonResponse
    {
        $score = 0;
        $password = $request->password;

        if (strlen($password) >= 12) {
            $score += 2;
        } elseif (strlen($password) >= 8) {
            $score += 1;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $score += 1;
        }
        if (preg_match('/[a-z]/', $password)) {
            $score += 1;
        }

        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        }

        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 1;
        }

        return response()->json([
            'score' => $score,
            'strength' => $this->getPasswordStrengthLabel($score),
            'suggestions' => $this->getPasswordSuggestions($score),
        ]);
    }

    protected function calculateSecurityScore(): int
    {
        $user = auth()->user();
        $score = 0;

        if ($user->two_factor_enabled) {
            $score += 30;
        }

        // ⚠️ Previously, this was recalculated from the password hash (not the password itself), which always
        // gave a high score. Now, the score stored at the time of registration/password change (on the raw password,
        // before hashing) is used; for older users for whom this column
        // has not yet been filled in, the share of this section remains zero (not a dummy number).
        $score += ($user->password_strength_score ?? 0) * 5;

        $expiryDays = SecuritySetting::get()->password_expiry_days;
        if ($user->password_changed_at && $user->password_changed_at->gt(now()->subDays($expiryDays))) {
            $score += 20;
        }

        $suspiciousActivities = SecurityLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('level', 'warning')
            ->count();

        if ($suspiciousActivities === 0) {
            $score += 20;
        } elseif ($suspiciousActivities <= 2) {
            $score += 10;
        }

        return min($score, 100);
    }

    protected function getPasswordStrengthLabel($score): string
    {
        return match (true) {
            $score >= 5 => 'قوی',
            $score >= 3 => 'متوسط',
            default => 'ضعیف'
        };
    }

    protected function getPasswordSuggestions($score): array
    {
        $suggestions = [];

        if ($score < 3) {
            $suggestions[] = 'از حروف بزرگ و کوچک استفاده کنید';
            $suggestions[] = 'از اعداد استفاده کنید';
        }
        if ($score < 4) {
            $suggestions[] = 'از کاراکترهای خاص مانند @#$% استفاده کنید';
        }
        if ($score < 5) {
            $suggestions[] = 'طول رمز عبور را افزایش دهید (حداقل 12 کاراکتر)';
        }

        return $suggestions;
    }

    protected function getRecentActivities(): Collection
    {
        return SecurityLog::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
    }

    protected function getLoginAttempts(): int
    {
        return SecurityLog::where('user_id', auth()->id())
            ->where('event', 'login_attempt')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }
}
