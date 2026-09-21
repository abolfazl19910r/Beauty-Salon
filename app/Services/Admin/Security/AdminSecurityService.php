<?php

namespace App\Services\Admin\Security;

use App\Models\SecuritySetting;
use App\Models\User;
use App\Repositories\Contracts\SecurityLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminSecurityService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SecurityLogRepositoryInterface $securityLogRepository,
    ) {}

    public function paginatedLogs(array $filters): LengthAwarePaginator
    {
        return $this->securityLogRepository->query()
            ->with('user:id,name,phone')
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->where('level', $level))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date)))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('created_at', '<=', Carbon::parse($date)))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();
    }

    public function paginatedUsers(?string $search): LengthAwarePaginator
    {
        return $this->userRepository->query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['securityLogs as suspicious_activity_count' => function ($query) {
                $query->where('level', 'warning')->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (User $user) {
                $user->last_successful_login_at = $this->securityLogRepository->getLastSuccessfulLoginAt($user->id);

                return $user;
            });
    }

    public function stats(): array
    {
        return [
            'logs_last_30_days' => $this->securityLogRepository->countSince(now()->subDays(30)),
            'warnings_last_30_days' => $this->securityLogRepository->countWarningsSince(now()->subDays(30)),
            'failed_logins_last_24h' => $this->securityLogRepository->countFailedLoginAttemptsSince(now()->subDay()),
            'users_with_2fa' => $this->userRepository->countWithTwoFactorEnabled(),
        ];
    }

    public function updateSettings(array $validated): SecuritySetting
    {
        $settings = SecuritySetting::get();
        $settings->update($validated);

        return $settings;
    }
}
