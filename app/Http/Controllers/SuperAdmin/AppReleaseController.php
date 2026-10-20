<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\Apps\AppReleaseService;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Support\CentralUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «نسخه‌های اپ» در پنل ابرمدیر (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰): آپلود APK اپ مشتری و همکار، انتشار/پنهان کردن (برگشت به نسخه‌ی قبل)، حذف.
 * صفحه‌ی عمومی «دانلود اپ ماهرو» (/app) همیشه آخرین نسخه‌ی منتشرشده را می‌دهد.
 */
class AppReleaseController extends Controller
{
    public function __construct(private readonly AppReleaseService $releases, private readonly SuperAdminAudit $audit) {}

    public function index(): View
    {
        $byVariant = [];
        foreach (array_keys(AppRelease::VARIANTS) as $variant) {
            $byVariant[$variant] = AppRelease::query()->with('uploader:id,name')->where('variant', $variant)
                ->orderByDesc('version_code')->get();
        }

        return view('superadmin.app-releases.index', [
            'byVariant' => $byVariant,
            'maxMb' => (int) config('api.app_release_max_mb'),
            'phpLimitMb' => self::phpUploadLimitMb(),
            'downloadPage' => CentralUrl::route('app-download.show'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('api.app_release_max_mb') * 1024;
        $input = $request->validate([
            'variant' => ['required', Rule::in(array_keys(AppRelease::VARIANTS))],
            'apk' => ['required', 'file', 'max:'.$maxKb, 'extensions:apk'],
            'version_name' => ['nullable', 'string', 'max:32', 'regex:/^[0-9A-Za-z.\-+]+$/'],
            'version_code' => ['nullable', 'integer', 'min:1', 'max:2100000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['nullable', 'boolean'],
        ], [
            'apk.max' => 'حجم فایل بیشتر از سقف '.config('api.app_release_max_mb').' مگابایت است.',
            'apk.extensions' => 'فقط فایل ‎.apk پذیرفته می‌شود.',
            'apk.uploaded' => 'آپلود نشد؛ احتمالاً upload_max_filesize یا post_max_size در تنظیمات PHP سرور کمتر از حجم فایل است.',
        ]);

        $release = $this->releases->store($request->file('apk'), [
            'variant' => $input['variant'],
            'version_name' => $input['version_name'] ?? null,
            'version_code' => isset($input['version_code']) ? (int) $input['version_code'] : null,
            'notes' => $input['notes'] ?? null,
            'is_required' => $request->boolean('is_required'),
        ], $request->user());

        $this->audit->record('app_release.upload', 'آپلود نسخه‌ی '.$release->version_name.' ('.$release->version_code.') اپ «'.AppRelease::appName($release->variant).'»');

        return redirect()->route('superadmin.app-releases.index')
            ->with('success', 'نسخه‌ی '.$release->version_name.' اپ «'.AppRelease::appName($release->variant).'» منتشر شد.');
    }

    public function update(Request $request, AppRelease $release): RedirectResponse
    {
        $input = $request->validate([
            'is_published' => ['required', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $before = $release->only(['is_published', 'is_required']);
        $release->update([
            'is_published' => (bool) $input['is_published'],
            'is_required' => $request->has('is_required') ? $request->boolean('is_required') : $release->is_required,
        ]);
        $this->audit->record('app_release.update', 'تغییر نسخه‌ی '.$release->version_name.' اپ «'.AppRelease::appName($release->variant).'»',
            null, SuperAdminAudit::diff($before, $release->only(['is_published', 'is_required'])));

        return back()->with('success', $release->is_published ? 'نسخه منتشر است.' : 'نسخه پنهان شد؛ صفحه‌ی دانلود نسخه‌ی قبلی منتشرشده را می‌دهد.');
    }

    public function destroy(AppRelease $release): RedirectResponse
    {
        $label = $release->version_name.' اپ «'.AppRelease::appName($release->variant).'»';
        $this->releases->delete($release);
        $this->audit->record('app_release.delete', 'حذف نسخه‌ی '.$label);

        return back()->with('success', 'نسخه‌ی '.$label.' حذف شد.');
    }

    /** کمترین مقدار upload_max_filesize و post_max_size (مگابایت)؛ ۰ = نامحدود */
    public static function phpUploadLimitMb(): int
    {
        $toMb = function (string $value): int {
            $value = trim($value);
            if ($value === '' || $value === '0' || $value === '-1') {
                return 0;
            }
            $number = (float) $value;

            return (int) floor(match (strtolower(substr($value, -1))) {
                'g' => $number * 1024,
                'm' => $number,
                'k' => $number / 1024,
                default => $number / 1048576,
            });
        };

        $limits = array_filter([$toMb((string) ini_get('upload_max_filesize')), $toMb((string) ini_get('post_max_size'))]);

        return $limits === [] ? 0 : min($limits);
    }
}
