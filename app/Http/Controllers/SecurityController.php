<?php

namespace App\Http\Controllers;

use App\Services\SecurityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SecurityController extends Controller
{
    protected SecurityLogService $securityLogService;

    public function __construct(SecurityLogService $securityLogService)
    {
        $this->securityLogService = $securityLogService;
    }

    public function dashboard()
    {
        $user = auth()->user();

        $data = [
            'two_factor_enabled' => $user->two_factor_enabled,
            'active_sessions' => $this->getActiveSessions(),
            'recent_activities' => $this->getRecentActivities(),
            'security_score' => $this->calculateSecurityScore(),
            'last_password_change' => $user->password_changed_at,
            'login_attempts' => $this->getLoginAttempts()
        ];

        return view('security.dashboard', $data);
    }

    public function getActiveSessions()
    {
        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->select(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $session->last_activity = Carbon::createFromTimestamp($session->last_activity);
                return $session;
            });
    }

    public function terminateSession($id)
    {
        if ($id === session()->getId()) {
            return response()->json([
                'error' => 'نمی‌توانید نشست فعلی را پایان دهید.'
            ], 422);
        }

        DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        $this->securityLogService->logSuspiciousActivity('session_terminated', [
            'session_id' => $id
        ]);

        return response()->json([
            'message' => 'نشست با موفقیت پایان یافت.'
        ]);
    }

    public function terminateAllSessions()
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->securityLogService->logSuspiciousActivity('all_sessions_terminated');

        return response()->json([
            'message' => 'تمام نشست‌های دیگر با موفقیت پایان یافتند.'
        ]);
    }

    public function getSecurityLogs(Request $request)
    {
        $logs = DB::table('security_logs')
            ->where('user_id', auth()->id())
            ->when($request->type, function ($query, $type) {
                return $query->where('event', $type);
            })
            ->when($request->date_from, function ($query, $date) {
                return $query->where('created_at', '>=', Carbon::parse($date));
            })
            ->when($request->date_to, function ($query, $date) {
                return $query->where('created_at', '<=', Carbon::parse($date));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('security.logs', compact('logs'));
    }

    public function getLoginHistory()
    {
        $history = DB::table('security_logs')
            ->where('user_id', auth()->id())
            ->where('event', 'login_attempt')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($history);
    }

    public function checkPasswordStrength(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8'
        ]);

        $score = 0;
        $password = $request->password;

        if (strlen($password) >= 12) $score += 2;
        elseif (strlen($password) >= 8) $score += 1;

        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/[a-z]/', $password)) $score += 1;

        if (preg_match('/[0-9]/', $password)) $score += 1;

        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 1;

        return response()->json([
            'score' => $score,
            'strength' => $this->getPasswordStrengthLabel($score),
            'suggestions' => $this->getPasswordSuggestions($score)
        ]);
    }

    protected function calculateSecurityScore()
    {
        $user = auth()->user();
        $score = 0;

        if ($user->two_factor_enabled) $score += 30;

        $passwordScore = Cache::remember('password_strength_'.$user->id, 60*24, function() use ($user) {
            return $this->calculatePasswordStrength($user->password);
        });
        $score += $passwordScore * 5;

        if ($user->password_changed_at && $user->password_changed_at->gt(now()->subDays(90))) {
            $score += 20;
        }

        $suspiciousActivities = DB::table('security_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('level', 'warning')
            ->count();

        if ($suspiciousActivities === 0) $score += 20;
        elseif ($suspiciousActivities <= 2) $score += 10;

        return min($score, 100);
    }

    protected function getPasswordStrengthLabel($score): string
    {
        return match(true) {
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

    protected function getRecentActivities(): \Illuminate\Support\Collection
    {
        return DB::table('security_logs')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    protected function getLoginAttempts(): int
    {
        return DB::table('security_logs')
            ->where('user_id', auth()->id())
            ->where('event', 'login_attempt')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    private function calculatePasswordStrength($password): int
    {
        $score = 0;

        $length = strlen($password);
        if ($length >= 12) {
            $score += 3;
        } elseif ($length >= 10) {
            $score += 2;
        } elseif ($length >= 8) {
            $score += 1;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[a-z]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 3;
        }

        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= 8) {
            $score += 2;
        }

        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 2;
        }
        if (preg_match('/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
            $score += 2;
        }

        $commonPasswords = ['password', '123456', 'qwerty', 'admin', '123456789', '12345'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $score = 0;
        }

        return max(0, min(10, $score));
    }
}
