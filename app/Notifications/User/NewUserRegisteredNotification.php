<?php

namespace App\Notifications\User;

use App\Models\User;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsNotificationSettings;

    public function __construct(private readonly User $newUser) {}

    protected function settingsSalonId(): ?int
    {
        return $this->newUser->salon_id;
    }

    public function via(object $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::USER_REGISTERED_ADMIN, ['database'], $notifiable);
    }

    /**
     * لینک ندارد: کاربرِ تازه ثبت‌نام‌شده همیشه مشتری است و پنل ادمین صفحه‌ی جزئیات مشتری ندارد
     * (admin.users.show فقط مدیران سالن را نشان می‌دهد و برای مشتری 404 می‌داد). زنگوله در نبود
     * لینک به صفحه‌ی خود اعلان می‌رود.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_user_registered',
            'user_id' => $this->newUser->id,
            'name' => $this->newUser->name,
            'phone' => $this->newUser->phone,
            'message' => 'یک کاربر جدید ثبت‌نام کرد: '.$this->newUser->name.' ('.$this->newUser->phone.')',
            'link' => null,
        ];
    }
}
