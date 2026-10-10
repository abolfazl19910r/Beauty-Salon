<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Specialist\Notification\SpecialistNotificationController;
use App\Http\Responses\ApiResponse;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

/**
 * اعلان‌های داخلی اپ همکار (بسته‌ی ۲؛ صندوق اعلان پیش از پوش بسته‌ی ۴). مثل پنل وب، اعلان‌های حساب کادر و رکورد
 * متخصص با هم، فقط سالن جاری (و بدون سالن) — UserNotification::limitToCurrentSalon. برخلاف وب صفحه‌بندی در دیتابیس.
 */
class StaffNotificationController extends StaffController
{
    public function index(Request $request): JsonResponse
    {
        $input = $request->validate(['unread' => ['nullable', 'boolean']]);

        $page = $this->query($request)
            ->when(! empty($input['unread']), fn (Builder $q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(
            collect($page->items())->map(fn (UserNotification $n) => $this->present($n))->values(),
            $this->pageMeta($page) + ['unread_count' => $this->query($request)->whereNull('read_at')->count()],
        );
    }

    public function read(Request $request, string $notificationId): JsonResponse
    {
        $notification = $this->query($request)->whereKey($notificationId)->first();

        if (! $notification) {
            throw new ApiException('not_found', 'اعلان پیدا نشد.', 404);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return ApiResponse::success($this->present($notification->fresh()));
    }

    public function readAll(Request $request): JsonResponse
    {
        $updated = $this->query($request)->whereNull('read_at')->update(['read_at' => now()]);

        return ApiResponse::success(['marked' => $updated]);
    }

    protected function query(Request $request): Builder
    {
        $user = $request->user();
        $specialist = $this->specialist($request);

        return UserNotification::limitToCurrentSalon(UserNotification::query()->where(fn (Builder $q) => $q
            ->where(fn (Builder $w) => $w->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id))
            ->orWhere(fn (Builder $w) => $w->where('notifiable_type', $specialist->getMorphClass())->where('notifiable_id', $specialist->id))));
    }

    protected function present(UserNotification $n): array
    {
        $data = $n->data ?? [];

        return [
            'id' => $n->id,
            'category' => SpecialistNotificationController::CATEGORY_MAP[$n->type] ?? 'other',
            'message' => $data['message'] ?? $data['description'] ?? 'اعلان جدید',
            'booking_id' => $data['booking_id'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String(),
            'created_at_jalali' => $n->created_at ? Jalalian::fromCarbon($n->created_at)->format('Y/m/d H:i') : null,
        ];
    }
}
