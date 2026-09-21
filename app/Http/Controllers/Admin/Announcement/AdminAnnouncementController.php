<?php

namespace App\Http\Controllers\Admin\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Admin\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function index(): View
    {
        $announcements = $this->announcementRepository->paginateAllOrdered(15);
        $totalAnnouncements = $this->announcementRepository->count();
        $activeAnnouncements = $this->announcementRepository->countActive();
        $pendingAnnouncements = $this->announcementRepository->countPending();
        $expiredAnnouncements = $this->announcementRepository->countExpired();

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
        $this->announcementRepository->create($request->validated());

        return redirect()->route('admin.announcements.index')
            ->with('success', 'اطلاعیه با موفقیت ایجاد شد.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->ensureSalonOwnership($announcement->salon_id);

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureSalonOwnership($announcement->salon_id);

        $this->announcementRepository->update($announcement, $request->validated());

        return redirect()->route('admin.announcements.index')
            ->with('success', 'اطلاعیه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->ensureSalonOwnership($announcement->salon_id);

        $this->announcementRepository->delete($announcement);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'اطلاعیه با موفقیت حذف شد.');
    }
}
