<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * درخواست‌های دموی صفحه‌ی فروش برای سوپرادمین (۲۰۲۶-۱۰-۱۴): فهرست (پیگیری‌نشده / پیگیری‌شده / همه) و «پیگیری شد» با یادداشت.
 * شمار پیگیری‌نشده‌ها در منوی کناری پنل سوپرادمین دیده می‌شود (layouts.superadmin) — پنل سوپرادمین صندوق اعلان ندارد.
 */
class DemoRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filter = in_array($request->query('status'), ['open', 'handled', 'all'], true) ? $request->query('status') : 'open';

        $requests = DemoRequest::query()
            ->with('handler:id,name')
            ->when($filter === 'open', fn ($q) => $q->whereNull('handled_at'))
            ->when($filter === 'handled', fn ($q) => $q->whereNotNull('handled_at'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('superadmin.demo-requests.index', [
            'requests' => $requests,
            'filter' => $filter,
            'stats' => [
                'open' => DemoRequest::query()->open()->count(),
                'handled' => DemoRequest::query()->whereNotNull('handled_at')->count(),
            ],
        ]);
    }

    public function update(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        $input = $request->validate([
            'handled' => ['required', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $demoRequest->update([
            'handled_at' => $input['handled'] ? ($demoRequest->handled_at ?? now()) : null,
            'handled_by' => $input['handled'] ? ($demoRequest->handled_by ?? $request->user()->id) : null,
            'admin_note' => $input['admin_note'] ?? $demoRequest->admin_note,
        ]);

        return back()->with('success', $input['handled'] ? 'درخواست پیگیری‌شده علامت خورد.' : 'درخواست دوباره باز شد.');
    }
}
