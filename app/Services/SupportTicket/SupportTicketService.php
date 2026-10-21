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
        $ticket = $this->supportTicketRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            // مدیرِ چندسالنی (تصمیم ۵): تیکت مال سالنی است که از پنلش ثبت شد
            'metadata' => array_filter(['salon_id' => app(\App\Support\CurrentSalon::class)->id()]) ?: null,
        ]);

        app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send('ticket', sprintf('تیکت تازه از «%s»: %s', $this->salonNameOf($ticket), $ticket->title),
            route('superadmin.support-tickets.show', $ticket->id, false), $this->salonOf($ticket)?->id);

        return $ticket;
    }

    public function addReply(SupportTicket $ticket, int $userId, string $message, bool $isStaffReply): SupportTicketMessage
    {
        $reply = $this->supportTicketMessageRepository->create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'message' => $message,
            'is_staff_reply' => $isStaffReply,
        ]);

        if ($isStaffReply && $ticket->isOpen()) {
            $this->supportTicketRepository->update($ticket, ['status' => 'in_progress']);
        } elseif (! $isStaffReply && ($ticket->isResolved() || $ticket->isClosed())) {
            $ticket->reopen();
        }

        if (! $isStaffReply) {
            app(\App\Services\SuperAdmin\SuperAdminNotifier::class)->send('ticket_reply', sprintf('پاسخ تازه‌ی «%s» در تیکت «%s».', $this->salonNameOf($ticket), $ticket->title),
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

    /** تیکت ستون سالن ندارد؛ سالن از کاربر ثبت‌کننده (مدیر سالن یا مشتری) */
    private function salonOf(SupportTicket $ticket): ?\App\Models\Salon
    {
        $salonId = is_array($ticket->metadata) ? ($ticket->metadata['salon_id'] ?? null) : null;
        if ($salonId) {
            return \App\Models\Salon::withoutGlobalScopes()->find($salonId);
        }

        $user = \App\Models\User::find($ticket->user_id);

        return $user?->salons()->withoutGlobalScopes()->first()
            ?? ($user?->salon_id ? \App\Models\Salon::withoutGlobalScopes()->find($user->salon_id) : null);
    }
}
