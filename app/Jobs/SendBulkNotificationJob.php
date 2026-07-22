<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Generic Job to send a notification to a large number of users/experts in bulk,
 * without executing the entire operation in a single request at the same time (and without a queue).
 *
 * ⭐ At the time of creating this file (R-Jobs phase), no current feature in the project uses this Job
 * — it is simply a ready-made infrastructure for future features that require
 * mass notification (e.g., a general notification to all users/experts,
 * which is not currently implemented in the project). As long as it does not have a real consumer,
 * just this one file + a static helper method (dispatchForModel) is enough; no new
 * Route/Controller/View is needed.
 *
 * Future usage (example):
 * SendBulkNotificationJob::dispatchForModel(
 * notificationClass: SomeAnnouncementNotification::class,
 * notificationArgs: [$announcement],
 * notifiableModel: User::class,
 * notifiableIds: User::pluck('id'),
 * );
 */
class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param class-string<\Illuminate\Notifications\Notification> $notificationClass
     * @param array<int, mixed> $notificationArgs The arguments to the notification class constructor, in that order
     * @param class-string<\Illuminate\Database\Eloquent\Model> $notifiableModel The model that receives the notification (User::class, Specialist::class, etc.)
     * @param array<int, int|string> $notifiableIds The IDs of this chunk (not the whole list)
     */
    public function __construct(
        protected string $notificationClass,
        protected array $notificationArgs,
        protected string $notifiableModel,
        protected array $notifiableIds,
    ) {
    }

    /**
     * Suggested entry point for future features — chunks the large list itself
     * * and dispatches several lighter jobs instead of one heavy job with thousands of records in memory.
     *
     * @param class-string<\Illuminate\Notifications\Notification> $notificationClass
     * @param array<int, mixed> $notificationArgs
     * @param class-string<\Illuminate\Database\Eloquent\Model> $notifiableModel
     * @param \Illuminate\Support\Collection<int, int|string>|array<int, int|string> $notifiableIds
     */
    public static function dispatchForModel(
        string $notificationClass,
        array $notificationArgs,
        string $notifiableModel,
        Collection|array $notifiableIds,
        int $chunkSize = 200,
    ): void {
        Collection::make($notifiableIds)
            ->values()
            ->chunk($chunkSize)
            ->each(function (Collection $chunk) use ($notificationClass, $notificationArgs, $notifiableModel): void {
                static::dispatch($notificationClass, $notificationArgs, $notifiableModel, $chunk->all());
            });
    }

    public function handle(): void
    {
        if (! is_subclass_of($this->notificationClass, Notification::class)) {
            Log::error('SendBulkNotificationJob: کلاس نوتیفیکیشن نامعتبر است', [
                'notification_class' => $this->notificationClass,
            ]);
            return;
        }

        $notifiables = $this->notifiableModel::query()
            ->whereIn('id', $this->notifiableIds)
            ->get();

        if ($notifiables->isEmpty()) {
            Log::warning('SendBulkNotificationJob: هیچ گیرنده‌ای برای این chunk پیدا نشد', [
                'notifiable_model' => $this->notifiableModel,
                'ids' => $this->notifiableIds,
            ]);
            return;
        }

        $notification = new $this->notificationClass(...$this->notificationArgs);

        NotificationFacade::send($notifiables, $notification);
    }
}
