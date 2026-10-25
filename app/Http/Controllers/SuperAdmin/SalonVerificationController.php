<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SalonVerification;
use App\Services\Salon\SalonVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * صف احراز سالن‌ها (بسته‌ی مجوز 5د، مرحله‌ی الف دستی): بررسی کد ملی مالک، نوع و شناسه‌ی یکتای پروانه‌ی کسب و فایل آن (در صورت
 * لزوم با استعلام دستی در درگاه ملی مجوزها)، و تأیید یا رد با دلیل. هر تصمیم در «گزارش کارهای ابرمدیر».
 */
class SalonVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : 'pending';

        return view('superadmin.verifications.index', [
            'verifications' => SalonVerification::query()->with(['salon' => fn ($q) => $q->withoutGlobalScopes()])
                ->where('status', $status)
                ->orderBy($status === 'pending' ? 'created_at' : 'reviewed_at', $status === 'pending' ? 'asc' : 'desc')
                ->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => SalonVerification::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
        ]);
    }

    public function show(SalonVerification $verification): View
    {
        $verification->load(['salon' => fn ($q) => $q->withoutGlobalScopes(), 'submitter', 'reviewer']);

        return view('superadmin.verifications.show', [
            'verification' => $verification,
            'history' => SalonVerification::query()->where('salon_id', $verification->salon_id)->whereKeyNot($verification->id)->latest('id')->get(),
            'sameNationalCode' => SalonVerification::query()->where('national_code_hash', $verification->national_code_hash)
                ->where('salon_id', '!=', $verification->salon_id)->with(['salon' => fn ($q) => $q->withoutGlobalScopes()])->get(),
        ]);
    }

    /** فایل پروانه از دیسک خصوصی — فقط ابرمدیر */
    public function file(SalonVerification $verification): StreamedResponse
    {
        abort_unless($verification->license_file_path && Storage::disk(SalonVerificationService::DISK)->exists($verification->license_file_path), 404);

        return Storage::disk(SalonVerificationService::DISK)->response($verification->license_file_path, 'license-'.$verification->salon_id.'.'.pathinfo($verification->license_file_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function approve(Request $request, SalonVerification $verification, SalonVerificationService $service): RedirectResponse
    {
        abort_unless($verification->status === 'pending', 409);
        $service->approve($verification, $request->user());

        return redirect()->route('superadmin.verifications.index')->with('success', "احراز «{$verification->salon->name}» تأیید شد.");
    }

    public function reject(Request $request, SalonVerification $verification, SalonVerificationService $service): RedirectResponse
    {
        abort_unless($verification->status === 'pending', 409);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'دلیل رد را بنویسید (برای مالک فرستاده می‌شود).']);
        $service->reject($verification, $request->user(), $data['reason']);

        return redirect()->route('superadmin.verifications.index')->with('success', "احراز «{$verification->salon->name}» رد شد.");
    }
}
