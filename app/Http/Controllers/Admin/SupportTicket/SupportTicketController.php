<?php

namespace App\Http\Controllers\Admin\SupportTicket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportTicket\StoreSupportTicketRequest;
use App\Http\Requests\SupportTicket\StoreSupportTicketReplyRequest;
use App\Models\SupportTicket;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use App\Services\SupportTicket\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        protected readonly SupportTicketRepositoryInterface $supportTicketRepository,
        protected readonly SupportTicketService $supportTicketService,
    ) {}

    public function index(): View
    {
        $tickets = $this->supportTicketRepository->paginateForUser(auth()->id());

        return view('admin.support-tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('admin.support-tickets.create');
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $ticket = $this->supportTicketService->createTicket(auth()->id(), $request->validated());

        return redirect()
            ->route('admin.support-tickets.show', $ticket)
            ->with('success', '✅ تیکت شما با موفقیت ثبت شد.');
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['messages.user', 'assignedTo']);

        return view('admin.support-tickets.show', compact('ticket'));
    }

    public function reply(StoreSupportTicketReplyRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $this->supportTicketService->addReply(
            $ticket,
            auth()->id(),
            $request->validated('message'),
            isStaffReply: false,
        );

        return back()->with('success', '✅ پیام شما ثبت شد.');
    }
}
