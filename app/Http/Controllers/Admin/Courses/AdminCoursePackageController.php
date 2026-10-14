<?php

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\CoursePackage;
use App\Models\CourseSetting;
use App\Models\Specialist;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * تعریف بسته‌های دوره‌ی درمانی و تنظیمات دوره‌های سالن (بسته‌ی ب) — فقط با امکان «دوره‌های درمانی» پلن (feature:treatment_courses).
 * بسته‌ای که فروش دارد حذف نرم می‌شود (دوره‌های خریده‌شده snapshot خودشان را دارند).
 */
class AdminCoursePackageController extends Controller
{
    public function index(): View
    {
        $packages = CoursePackage::query()->with(['service', 'specialist'])->withCount('customerCourses')->orderBy('sort')->orderBy('id')->get();
        $setting = CourseSetting::forSalon((int) app(CurrentSalon::class)->id());

        return view('admin.courses.packages.index', compact('packages', 'setting'));
    }

    public function create(): View
    {
        return view('admin.courses.packages.form', $this->formData(new CoursePackage([
            'sessions_count' => 6, 'interval_days' => 35, 'min_interval_days' => 28, 'validity_days' => 365, 'is_active' => true, 'is_public' => true,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        CoursePackage::create($this->validated($request));

        return redirect()->route('admin.courses.packages.index')->with('success', 'بسته‌ی دوره ساخته شد.');
    }

    public function edit(CoursePackage $package): View
    {
        return view('admin.courses.packages.form', $this->formData($package));
    }

    public function update(Request $request, CoursePackage $package): RedirectResponse
    {
        $package->update($this->validated($request, $package));

        return redirect()->route('admin.courses.packages.index')->with('success', 'بسته‌ی دوره ذخیره شد. دوره‌هایی که قبلاً فروخته شده‌اند تغییری نمی‌کنند.');
    }

    public function destroy(CoursePackage $package): RedirectResponse
    {
        $package->delete();

        return redirect()->route('admin.courses.packages.index')->with('success', 'بسته حذف شد؛ دوره‌های خریده‌شده‌ی آن سر جایشان هستند.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $salonId = (int) app(CurrentSalon::class)->id();
        CourseSetting::withoutGlobalScope('salon')->updateOrCreate(['salon_id' => $salonId], ['burn_late_cancellation' => $request->boolean('burn_late_cancellation')]);

        return back()->with('success', 'تنظیمات دوره‌ها ذخیره شد.');
    }

    private function formData(CoursePackage $package): array
    {
        return [
            'package' => $package,
            'services' => BeautyService::query()->orderBy('name')->get(),
            'specialists' => Specialist::query()->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?CoursePackage $package = null): array
    {
        $digits = fn ($v) => is_string($v) ? strtr($v, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', ',' => '', '٬' => '']) : $v;
        foreach (['price', 'sessions_count', 'interval_days', 'min_interval_days', 'validity_days', 'min_prepayment_percent', 'sort'] as $field) {
            $request->merge([$field => ($v = $digits($request->input($field))) === '' ? null : $v]);
        }
        $salonId = (int) app(CurrentSalon::class)->id();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'service_id' => ['required', 'integer', Rule::exists('beauty_services', 'id')->where('salon_id', $salonId)],
            'specialist_id' => ['nullable', 'integer', Rule::exists('specialists', 'id')->where('salon_id', $salonId)],
            'sessions_count' => ['required', 'integer', 'min:2', 'max:30'],
            'price' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'min_prepayment_percent' => ['nullable', 'integer', 'min:10', 'max:99'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_interval_days' => ['required', 'integer', 'min:0', 'lte:interval_days'],
            'validity_days' => ['nullable', 'integer', 'min:7', 'max:1825'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ], [
            'min_interval_days.lte' => 'فاصله‌ی حداقل نباید از فاصله‌ی پیشنهادی بیشتر باشد.',
        ], [
            'name' => 'نام', 'service_id' => 'خدمت', 'sessions_count' => 'تعداد جلسه', 'price' => 'قیمت', 'interval_days' => 'فاصله‌ی پیشنهادی',
            'min_interval_days' => 'فاصله‌ی حداقل', 'validity_days' => 'مهلت', 'min_prepayment_percent' => 'درصد پیش‌پرداخت',
        ]);

        return array_merge($data, [
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'sort' => $data['sort'] ?? 0,
            'specialist_id' => $data['specialist_id'] ?? null,
            'validity_days' => $data['validity_days'] ?? null,
            'min_prepayment_percent' => $data['min_prepayment_percent'] ?? null,
        ]);
    }
}
