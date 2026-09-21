<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SupportTicket\UpdateSupportTicketRequest;
use App\Http\Requests\SupportTicket\StoreSupportTicketReplyRequest;
use App\Models\SupportTicket;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use App\Services\SupportTicket\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        protected readonly SupportTicketRepositoryInterface $supportTicketRepository,
        protected readonly SupportTicketService $supportTicketService,
    ) {}

    public function index(Request $request): View
    {
        $tickets = $this->supportTicketRepository->paginateWithFilters(
            $request->only(['status', 'priority', 'category', 'assigned'])
        );

        $stats = [
            'open' => $this->supportTicketRepository->countByStatus('open'),
            'in_progress' => $this->supportTicketRepository->countByStatus('in_progress'),
            'resolved' => $this->supportTicketRepository->countByStatus('resolved'),
            'closed' => $this->supportTicketRepository->countByStatus('closed'),
        ];

        return view('superadmin.support-tickets.index', compact('tickets', 'stats'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['messages.user', 'user.salons', 'assignedTo']);

        return view('superadmin.support-tickets.show', compact('ticket'));
    }

    public function reply(StoreSupportTicketReplyRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->supportTicketService->addReply(
            $ticket,
            auth()->id(),
            $request->validated('message'),
            isStaffReply: true,
        );

        return back()->with('success', '✅ پاسخ شما ثبت شد.');
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validated();

        if (array_key_exists('assigned_to', $validated) && $validated['assigned_to']) {
            $this->supportTicketService->assign($ticket, (int) $validated['assigned_to']);
        }

        if (! empty($validated['priority'])) {
            $ticket->update(['priority' => $validated['priority']]);
        }

        return back()->with('success', '✅ تیکت به‌روزرسانی شد.');
    }

    public function resolve(SupportTicket $ticket): RedirectResponse
    {
        $this->supportTicketService->markResolved($ticket);

        return back()->with('success', '✅ تیکت به‌عنوان حل‌شده علامت‌گذاری شد.');
    }

    public function close(SupportTicket $ticket): RedirectResponse
    {
        $this->supportTicketService->markClosed($ticket);

        return back()->with('success', '✅ تیکت بسته شد.');
    }

    public function reopen(SupportTicket $ticket): RedirectResponse
    {
        $this->supportTicketService->reopen($ticket);

        return back()->with('success', '✅ تیکت دوباره باز شد.');
    }
}
