<?php

namespace App\Http\Controllers\Admin\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Admin\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Previously, this controller only passed counters to index.blade.php, and the actual CRUD
 * (store/update/destroy) simply returned JSON for AnnouncementAdmin.jsx
 * (React SPA) to consume. Since BlogAdmin.jsx was removed, its broken import
 * in admin.jsx would have buried the entire admin bundle (not just the blog) — including this
 * page. A full conversion to Blade both eliminates that risk and aligns with the project's decision
 * ("React → Blade for simple admin pages").
 */
class AdminAnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        $totalAnnouncements = Announcement::count();

        $activeAnnouncements = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $pendingAnnouncements = Announcement::where('is_active', true)
            ->where('published_at', '>', now())
            ->count();

        $expiredAnnouncements = Announcement::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        return view('admin.announcements.index', compact(
            'announcements',
            'totalAnnouncements',
            'activeAnnouncements',
            'pendingAnnouncements',
            'expiredAnnouncements'
        ));
    }

    public function create(): View
    {
        return view('admin.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        Announcement::create($request->validated());

        return redirect()->route('admin.announcements.index')
            ->with('success', 'اطلاعیه با موفقیت ایجاد شد.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        return redirect()->route('admin.announcements.index')
            ->with('success', 'اطلاعیه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'اطلاعیه با موفقیت حذف شد.');
    }
}
