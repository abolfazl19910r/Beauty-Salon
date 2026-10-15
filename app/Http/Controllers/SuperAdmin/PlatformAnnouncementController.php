<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAnnouncement;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Support\JalaliDateInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** ⭐ «اطلاعیه‌ی سراسری» ابرمدیر به همه‌ی سالن‌ها (۲۰۲۶-۱۰-۱۵) — بنر بالای پنل مدیر و/یا متخصص */
class PlatformAnnouncementController extends Controller
{
    public function __construct(private readonly SuperAdminAudit $audit) {}

    public function index(): View
    {
        return view('superadmin.announcements.index', ['announcements' => PlatformAnnouncement::query()->latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('superadmin.announcements.form', ['announcement' => new PlatformAnnouncement(['level' => 'info', 'audience' => 'admins', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $announcement = PlatformAnnouncement::create($this->validated($request) + ['created_by' => $request->user()->id]);
        $this->audit->record('announcement.saved', "اطلاعیه‌ی سراسری تازه: «{$announcement->title}» (".PlatformAnnouncement::AUDIENCES[$announcement->audience].')');

        return redirect()->route('superadmin.announcements.index')->with('success', 'اطلاعیه ذخیره شد.');
    }

    public function edit(PlatformAnnouncement $announcement): View
    {
        return view('superadmin.announcements.form', compact('announcement'));
    }

    public function update(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));
        $this->audit->record('announcement.saved', "ویرایش اطلاعیه‌ی سراسری «{$announcement->title}»".($announcement->is_active ? '' : ' (خاموش)'));

        return redirect()->route('superadmin.announcements.index')->with('success', 'اطلاعیه ذخیره شد.');
    }

    public function destroy(PlatformAnnouncement $announcement): RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();
        $this->audit->record('announcement.deleted', "حذف اطلاعیه‌ی سراسری «{$title}»");

        return redirect()->route('superadmin.announcements.index')->with('success', 'اطلاعیه حذف شد.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'level' => ['required', Rule::in(array_keys(PlatformAnnouncement::LEVELS))],
            'audience' => ['required', Rule::in(array_keys(PlatformAnnouncement::AUDIENCES))],
            'starts_at' => ['nullable', 'string', 'max:20'],
            'ends_at' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['title' => 'عنوان', 'body' => 'متن', 'starts_at' => 'شروع نمایش', 'ends_at' => 'پایان نمایش']);

        $starts = JalaliDateInput::toCarbon($data['starts_at'] ?? null);
        $ends = JalaliDateInput::toCarbon($data['ends_at'] ?? null, endOfDay: true);
        $errors = [];
        if (! empty($data['starts_at']) && ! $starts) {
            $errors['starts_at'] = 'تاریخ شمسی معتبر نیست (مثلاً ۱۴۰۵/۰۸/۰۱).';
        }
        if (! empty($data['ends_at']) && ! $ends) {
            $errors['ends_at'] = 'تاریخ شمسی معتبر نیست (مثلاً ۱۴۰۵/۰۸/۳۰).';
        }
        if ($starts && $ends && $ends->lte($starts)) {
            $errors['ends_at'] = 'پایان نمایش باید بعد از شروع باشد.';
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return [
            'title' => $data['title'], 'body' => $data['body'], 'level' => $data['level'], 'audience' => $data['audience'],
            'starts_at' => $starts, 'ends_at' => $ends, 'is_active' => $request->boolean('is_active'),
        ];
    }
}
