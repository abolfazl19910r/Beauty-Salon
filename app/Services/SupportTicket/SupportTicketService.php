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
        return $this->supportTicketRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
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

        if ($isStaffReply && $ticket->isOpen()) {
            $this->supportTicketRepository->update($ticket, ['status' => 'in_progress']);
        } elseif (! $isStaffReply && ($ticket->isResolved() || $ticket->isClosed())) {
            $ticket->reopen();
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
}
