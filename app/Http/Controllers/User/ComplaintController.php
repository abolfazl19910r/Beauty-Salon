<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\SupportTicket\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «شکایت و پیگیری» مشتری (بسته‌ی مجوز 5د): شکایت از سالن یا از پلتفرم، با پاسخ ابرمدیر ظرف ۷۲ ساعت (دستورالعمل اینماد ۳-۸).
 * روی همان تیکت پشتیبانی (kind=complaint)؛ فقط خود مشتری و ابرمدیر آن را می‌بینند — مدیر سالن نه (تصمیم ۲۰۲۶-۱۰-۲۵).
 */
class ComplaintController extends Controller
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function index(Request $request): View
    {
        $complaints = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->where('kind', SupportTicket::KIND_COMPLAINT)
            ->latest()
            ->paginate(15);

        return view('complaints.index', ['complaints' => $complaints, 'hours' => (int) config('legal.complaint_response_hours', 72)]);
    }

    public function create(): View
    {
        return view('complaints.create', ['targets' => SupportTicket::COMPLAINT_TARGETS, 'hours' => (int) config('legal.complaint_response_hours', 72)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', 'in:'.implode(',', array_keys(SupportTicket::COMPLAINT_TARGETS))],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'target.required' => 'موضوع شکایت را انتخاب کنید.',
            'title.required' => 'عنوان شکایت را بنویسید.',
            'description.required' => 'شرح شکایت را بنویسید.',
            'description.min' => 'شرح شکایت دست‌کم ۱۰ حرف باشد.',
        ]);

        $ticket = $this->tickets->createComplaint($request->user()->id, $data['target'], $data['title'], $data['description']);

        return redirect()->route('complaints.show', $ticket)
            ->with('success', 'شکایت شما ثبت شد. حداکثر ظرف '.to_persian_num((string) config('legal.complaint_response_hours', 72)).' ساعت پاسخ می‌دهیم.');
    }

    public function show(Request $request, SupportTicket $complaint): View
    {
        $this->authorizeComplaint($request, $complaint);
        $complaint->load('messages.user');

        return view('complaints.show', ['complaint' => $complaint]);
    }

    public function reply(Request $request, SupportTicket $complaint): RedirectResponse
    {
        $this->authorizeComplaint($request, $complaint);
        $data = $request->validate(['message' => ['required', 'string', 'max:3000']], ['message.required' => 'متن پیام را بنویسید.']);

        $this->tickets->addReply($complaint, $request->user()->id, $data['message'], isStaffReply: false);

        return back()->with('success', 'پیام شما ثبت شد.');
    }

    private function authorizeComplaint(Request $request, SupportTicket $complaint): void
    {
        // ۴۰۴ (نه ۴۰۳) تا وجود شکایت دیگران تأیید نشود
        abort_unless($complaint->isComplaint() && $complaint->user_id === $request->user()->id, 404);
    }
}
