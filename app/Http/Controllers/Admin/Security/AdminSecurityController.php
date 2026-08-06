<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Security\UpdateSecuritySettingsRequest;
use App\Models\SecuritySetting;
use App\Services\Admin\Security\AdminSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSecurityController extends Controller
{
    public function __construct(protected readonly AdminSecurityService $service)
    {
    }

    public function logs(Request $request): View
    {
        return view('admin.security.logs', [
            'logs' => $this->service->paginatedLogs($request->only(['event', 'level', 'user_id', 'date_from', 'date_to'])),
            'stats' => $this->service->stats(),
        ]);
    }

    public function users(Request $request): View
    {
        return view('admin.security.users', [
            'users' => $this->service->paginatedUsers($request->get('search')),
            'search' => $request->get('search'),
        ]);
    }

    public function settings(): View
    {
        return view('admin.security.settings', [
            'settings' => SecuritySetting::get(),
        ]);
    }

    public function updateSettings(UpdateSecuritySettingsRequest $request): RedirectResponse
    {
        $this->service->updateSettings($request->validated());

        return back()->with('success', 'تنظیمات امنیتی با موفقیت به‌روزرسانی شد.');
    }
}
