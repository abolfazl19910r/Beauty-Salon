<?php

namespace App\Http\Controllers\Admin\Verification;

use App\Http\Controllers\Controller;
use App\Models\SalonVerification;
use App\Rules\IranianNationalCode;
use App\Services\Salon\SalonVerificationService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «احراز سالن» در پنل مالک (بسته‌ی مجوز 5د، تصمیم ۲۰۲۶-۱۰-۲۵: بعد از ثبت‌نام، در پنل): کد ملی مالک، نوع پروانه، شناسه‌ی یکتای
 * پروانه‌ی کسب (درگاه ملی مجوزها) و فایل پروانه. فقط مالک سالن (گروه salon.owner).
 */
class AdminSalonVerificationController extends Controller
{
    public function __construct(private readonly CurrentSalon $currentSalon) {}

    public function show(): View
    {
        $salon = $this->currentSalon->get();

        return view('admin.verification.show', [
            'salon' => $salon,
            'latest' => $salon->verifications()->first(),
            'licenseTypes' => SalonVerification::LICENSE_TYPES,
        ]);
    }

    public function store(Request $request, SalonVerificationService $service): RedirectResponse
    {
        $salon = $this->currentSalon->get();
        if (in_array($salon->verification_status, ['pending', 'approved'], true)) {
            return back()->with('error', $salon->verification_status === 'approved' ? 'سالن احراز شده است.' : 'مدارک در انتظار بررسی است.');
        }

        $request->merge(['owner_national_code' => IranianNationalCode::normalize((string) $request->input('owner_national_code'))]);
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:190'],
            'owner_national_code' => ['required', new IranianNationalCode],
            'license_type' => ['required', Rule::in(array_keys(SalonVerification::LICENSE_TYPES))],
            'license_unique_id' => ['required', 'string', 'regex:/^[0-9A-Za-z\-]{6,40}$/'],
            'license_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'owner_name.required' => 'نام مالک (همان نام روی پروانه) را بنویسید.',
            'license_type.required' => 'نوع پروانه را انتخاب کنید.',
            'license_unique_id.required' => 'شناسه‌ی یکتای پروانه را بنویسید.',
            'license_unique_id.regex' => 'شناسه‌ی یکتا فقط رقم و حرف لاتین (۶ تا ۴۰ نویسه).',
            'license_file.required' => 'فایل پروانه‌ی کسب را بفرستید.',
            'license_file.mimes' => 'فایل پروانه باید PDF یا عکس (JPG، PNG) باشد.',
            'license_file.max' => 'فایل پروانه حداکثر ۵ مگابایت.',
        ]);

        $service->submit($salon, $request->user(), $data, $request->file('license_file'));

        return redirect()->route('admin.verification.show')->with('success', 'مدارک فرستاده شد؛ پس از بررسی، نتیجه را پیامک می‌کنیم.');
    }
}
