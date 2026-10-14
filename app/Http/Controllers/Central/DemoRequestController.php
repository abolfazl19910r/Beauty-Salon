<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreDemoRequestRequest;
use App\Models\DemoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * POST /demo-request — فرم «درخواست دموی رایگان» صفحه‌ی فروش (لندینگ تازه، تصمیم ۲۰۲۶-۱۰-۱۴).
 * اسکریپت صفحه با fetch و Accept: application/json می‌فرستد (۲۰۱)؛ بدون جاوااسکریپت فرم عادی است و به همان بخش صفحه برمی‌گردد.
 * محافظ‌ها: سقف نرخ مسیر (throttle)، فیلد تله‌ی website (ربات → پاسخ موفق ولی ذخیره نمی‌شود)، و هر شماره در ۲۴ ساعت یک ردیف
 * (درخواست تکراری همان ردیف را تازه می‌کند تا فهرست سوپرادمین شلوغ نشود).
 */
class DemoRequestController extends Controller
{
    public const DUPLICATE_WINDOW_HOURS = 24;

    public function store(StoreDemoRequestRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['website'] ?? null)) {
            $attributes = [
                'name' => $data['name'],
                'salon_name' => $data['salon'] ?? null,
                'salon_type' => $data['type'] ?? null,
                'ip' => $request->ip(),
            ];

            $recent = DemoRequest::query()
                ->where('phone', $data['phone'])
                ->where('created_at', '>=', now()->subHours(self::DUPLICATE_WINDOW_HOURS))
                ->latest('id')
                ->first();

            $recent
                ? $recent->update($attributes)
                : DemoRequest::create($attributes + ['phone' => $data['phone']]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'درخواست شما ثبت شد.'], 201);
        }

        return redirect()->to(route('central.home').'#start')->with('demo_requested', true);
    }
}
