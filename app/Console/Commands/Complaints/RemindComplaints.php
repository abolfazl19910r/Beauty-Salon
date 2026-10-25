<?php

namespace App\Console\Commands\Complaints;

use App\Models\SupportTicket;
use App\Notifications\SuperAdmin\ComplaintDueNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\SuperAdmin\SuperAdminNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * یادآوری مهلت پاسخ به شکایت (بسته‌ی مجوز 5د) — هر ساعت. شکایت بی‌پاسخی که کمتر از ۲۴ ساعت مهلت دارد یا مهلتش گذشته، و از
 * آخرین یادآوری‌اش دست‌کم ۶ ساعت گذشته، در یک اعلان صندوق و یک پیامک به همه‌ی ابرمدیرها جمع می‌شود (نه یک پیامک برای هر شکایت).
 */
class RemindComplaints extends Command
{
    protected $signature = 'complaints:remind';

    protected $description = 'Remind super admins of complaints whose 72-hour response deadline is near or past';

    private const SOON_HOURS = 24;

    private const REPEAT_HOURS = 6;

    public function handle(UserRepositoryInterface $users, SuperAdminNotifier $notifier): int
    {
        $due = SupportTicket::query()->awaitingResponse()
            ->whereNotNull('response_due_at')
            ->where('response_due_at', '<=', now()->addHours(self::SOON_HOURS))
            ->where(fn ($q) => $q->whereNull('reminded_at')->orWhere('reminded_at', '<=', now()->subHours(self::REPEAT_HOURS)))
            ->get();

        if ($due->isEmpty()) {
            $this->line('nothing due');

            return self::SUCCESS;
        }

        $overdue = $due->filter(fn (SupportTicket $t) => $t->response_due_at->isPast())->count();
        $soon = $due->count() - $overdue;

        $notifier->send('complaint_due', ComplaintDueNotification::smsText($overdue, $soon),
            route('superadmin.support-tickets.index', ['kind' => 'awaiting'], false));

        $superAdmins = $users->getSuperAdmins();
        if ($superAdmins->isNotEmpty()) {
            Notification::send($superAdmins, new ComplaintDueNotification($overdue, $soon));
        }

        SupportTicket::query()->whereIn('id', $due->modelKeys())->update(['reminded_at' => now()]);
        $this->line("reminded: {$overdue} overdue, {$soon} due soon");

        return self::SUCCESS;
    }
}
