<?php

namespace App\Services\SupportTicket;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Repositories\Contracts\SupportTicketMessageRepositoryInterface;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;

class SupportTicketService
{
    public function __construct(
        protected readonly SupportTicketRepositoryInterface $supportTicketRepository,
        protected readonly SupportTicketMessageRepositoryInterface $supportTicketMessageRepository,
    ) {}

    public function createTicket(int $userId, array $data): SupportTicket
    {
        $complaint = ($data['kind'] ?? null) === SupportTicket::KIND_COMPLAINT;
        $salonId = app(\App\Support\CurrentSalon::class)->id();

        $ticket = $this->supportTicketRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'priority' => $data['priority'] ?? ($complaint ? 'high' : 'medium'),
            'status' => 'open',
            // مدیرِ چندسالنی (تصمیم ۵): تیکت مال سالنی است که از پنلش ثبت شد
            'metadata' => array_filter(['salon_id' => $salonId]) ?: null,
            'salon_id' => $salonId,
            // ⭐ بسته‌ی مجوز 5د: شکایت با مهلت پاسخ (دستورالعمل اینماد ۳-۸: ۷۲ ساعت؛ ۵ شکایت بی‌پاسخ = تعلیق اینماد)
            'kind' => $complaint ? SupportTicket::KIND_COMPLAINT : SupportTicket::KIND_SUPPORT,
            'response_due_at' => $complaint ? now()->addHours((int) config('legal.complaint_response_hours', 72)) : null,
        ]);

        app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send($complaint ? 'complaint' : 'ticket',
            sprintf($complaint ? 'شکایت تازه (مهلت پاسخ %s ساعت) — «%s»: %s' : 'تیکت تازه از «%2$s»: %3$s',
                to_persian_num((string) config('legal.complaint_response_hours', 72)), $this->salonNameOf($ticket), $ticket->title),
            route('superadmin.support-tickets.show', $ticket->id, false), $this->salonOf($ticket)?->id);

        return $ticket;
    }

    /**
     * شکایت مشتری از سالن یا از پلتفرم (بسته‌ی مجوز 5د). فقط خود مشتری و ابرمدیر آن را می‌بینند (تصمیم ۲۰۲۶-۱۰-۲۵: سالن نه).
     *
     * @param  'complaint_salon'|'complaint_platform'  $target
     */
    public function createComplaint(int $userId, string $target, string $title, string $description): SupportTicket
    {
        return $this->createTicket($userId, [
            'kind' => SupportTicket::KIND_COMPLAINT,
            'category' => $target,
            'title' => $title,
            'description' => $description,
        ]);
    }

    public function addReply(SupportTicket $ticket, int $userId, string $message, bool $isStaffReply): SupportTicketMessage
    {
        $reply = $this->supportTicketMessageRepository->create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'message' => $message,
            'is_staff_reply' => $isStaffReply,
        ]);

        // اولین پاسخ کادر پلتفرم = پاسخ به شکایت در مهلت (بسته‌ی مجوز 5د)
        if ($isStaffReply && $ticket->first_responded_at === null) {
            $this->supportTicketRepository->update($ticket, ['first_responded_at' => now()]);
        }

        if ($isStaffReply && $ticket->isOpen()) {
            $this->supportTicketRepository->update($ticket, ['status' => 'in_progress']);
        } elseif (! $isStaffReply && ($ticket->isResolved() || $ticket->isClosed())) {
            $ticket->reopen();
        }

        if (! $isStaffReply) {
            app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send('ticket_reply', sprintf('پاسخ تازه‌ی «%s» در %s «%s».', $this->salonNameOf($ticket), $ticket->isComplaint() ? 'شکایت' : 'تیکت', $ticket->title),
                route('superadmin.support-tickets.show', $ticket->id, false), $this->salonOf($ticket)?->id);
        }

        return $reply;
    }

    public function assign(SupportTicket $ticket, int $assigneeId): void
    {
        $ticket->assignTo($assigneeId);
    }

    public function markResolved(SupportTicket $ticket): void
    {
        $ticket->markAsResolved();
    }

    public function markClosed(SupportTicket $ticket): void
    {
        $ticket->markAsClosed();
    }

    public function reopen(SupportTicket $ticket): void
    {
        $ticket->reopen();
    }

    private function salonNameOf(SupportTicket $ticket): string
    {
        return (string) ($this->salonOf($ticket)?->name ?? 'بدون سالن');
    }

    /** سالن تیکت: ستون salon_id (از بسته‌ی مجوز 5د)، metadata قدیمی، یا کاربر ثبت‌کننده (مدیر سالن یا مشتری) */
    private function salonOf(SupportTicket $ticket): ?\App\Models\Salon
    {
        $salonId = $ticket->salon_id ?? (is_array($ticket->metadata) ? ($ticket->metadata['salon_id'] ?? null) : null);
        if ($salonId) {
            return \App\Models\Salon::withoutGlobalScopes()->find($salonId);
        }

        $user = \App\Models\User::find($ticket->user_id);

        return $user?->salons()->withoutGlobalScopes()->first()
            ?? ($user?->salon_id ? \App\Models\Salon::withoutGlobalScopes()->find($user->salon_id) : null);
    }
}
