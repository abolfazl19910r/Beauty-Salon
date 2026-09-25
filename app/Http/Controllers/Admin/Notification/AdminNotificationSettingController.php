<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use App\Services\Notification\NotificationSettingService;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationSettingController extends Controller
{
    public function __construct(
        protected readonly NotificationSettingService $service,
        protected readonly NotificationSettingRepositoryInterface $notificationSettingRepository,
    ) {}

    public function index(): View
    {
        // Ensure that every event recorded in the registry, even if it has never occurred to date, has at least
        // a default row in the table so that the admin can set it right there (without waiting for
        // the actual event to occur).
        $salonId = app(CurrentSalon::class)->id();

        foreach (NotificationEvents::allKeys() as $key) {
            $this->service->isEnabled($key, 'sms', $salonId);
        }

        $settings = $this->notificationSettingRepository
            ->getByEventKeys(NotificationEvents::allKeys(), $salonId)
            ->keyBy('event_key');

        return view('admin.notification-settings.index', [
            'groups' => NotificationEvents::groups(),
            'settings' => $settings,
            'botConfigured' => (bool) (config('services.telegram.bot_token') || config('services.bale.bot_token')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validKeys = NotificationEvents::allKeys();
        $salonId = app(CurrentSalon::class)->id();

        foreach ($validKeys as $key) {
            $safeKey = str_replace('.', '__', $key);

            $this->notificationSettingRepository->updateOrCreateForEvent($key, [
                'sms_enabled' => $request->boolean("sms.{$safeKey}"),
                'database_enabled' => $request->boolean("database.{$safeKey}"),
                'telegram_enabled' => $request->boolean("telegram.{$safeKey}"),
            ], $salonId);
        }

        $this->service->flush($salonId);

        return back()->with('success', 'تنظیمات اطلاع‌رسانی با موفقیت ذخیره شد.');
    }
}
