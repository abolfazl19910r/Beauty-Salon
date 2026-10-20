@extends('layouts.superadmin')

@section('title', 'نسخه‌های اپ')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="font-bold text-lg">نسخه‌های اپ</h2>
            <p class="text-sm mt-1" style="color: var(--sa-text-dim);">
                اپ‌ها در مارکت نیستند؛ صفحه‌ی عمومی دانلود همیشه آخرین نسخه‌ی منتشرشده‌ی هر اپ را می‌دهد:
                <a href="{{ $downloadPage }}" target="_blank" rel="noopener" dir="ltr" style="color: var(--sa-accent);">{{ $downloadPage }}</a>
            </p>
        </div>
    </div>

    @if (request()->boolean('too_large'))
        <div class="sa-card sa-card-pad mb-5" data-upload-too-large style="border-color: var(--sa-danger);">
            <p class="text-sm" style="color: var(--sa-danger);">فایل از سقف آپلود PHP این سرور (<span dir="ltr">post_max_size</span>) بزرگ‌تر بود و آپلود نشد.</p>
        </div>
    @endif

    @if ($phpLimitMb > 0 && $phpLimitMb < $maxMb)
        <div class="sa-card sa-card-pad mb-5" data-php-upload-limit style="border-color: var(--sa-warn);">
            <p class="text-sm" style="color: var(--sa-warn);">
                سقف آپلود PHP این سرور <span class="persian-number">{{ to_persian_num((string) $phpLimitMb) }}</span> مگابایت است
                (کمترِ <span dir="ltr">upload_max_filesize</span> و <span dir="ltr">post_max_size</span>). APK بزرگ‌تر از این آپلود نمی‌شود؛ هر دو را در
                php.ini دست‌کم <span class="persian-number">{{ to_persian_num((string) $maxMb) }}</span>M کنید.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        @foreach ($byVariant as $variant => $releases)
            @php($meta = \App\Models\AppRelease::VARIANTS[$variant])
            <div class="sa-card sa-card-pad" data-app-variant="{{ $variant }}">
                <h3 class="sa-card-title">اپ «{{ $meta['name'] }}»</h3>
                <p class="sa-card-desc mb-4">برای {{ $meta['audience'] }} — شناسه‌ی <span dir="ltr">{{ \App\Models\AppRelease::packageFor($variant) }}</span></p>

                <form method="POST" action="{{ route('superadmin.app-releases.store') }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-3 mb-5">
                    @csrf
                    <input type="hidden" name="variant" value="{{ $variant }}">
                    <div class="md:col-span-2">
                        <label class="sa-label" for="apk-{{ $variant }}">فایل APK (حداکثر <span class="persian-number">{{ to_persian_num((string) $maxMb) }}</span> مگابایت)</label>
                        <input id="apk-{{ $variant }}" type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required class="sa-input">
                    </div>
                    <div>
                        <label class="sa-label" for="vn-{{ $variant }}">نام نسخه (اختیاری)</label>
                        <input id="vn-{{ $variant }}" name="version_name" dir="ltr" placeholder="0.3.0" class="sa-input" style="text-align:left">
                    </div>
                    <div>
                        <label class="sa-label" for="vc-{{ $variant }}">شماره‌ی نسخه versionCode (اختیاری)</label>
                        <input id="vc-{{ $variant }}" name="version_code" dir="ltr" inputmode="numeric" placeholder="8" class="sa-input" style="text-align:left">
                    </div>
                    <p class="md:col-span-2 text-xs" style="color: var(--sa-text-dim);">نسخه از خود APK خوانده می‌شود؛ این دو فیلد فقط وقتی لازم است که خواندن نشود.</p>
                    <div class="md:col-span-2">
                        <label class="sa-label" for="notes-{{ $variant }}">تغییرات این نسخه (به کاربر نشان داده می‌شود)</label>
                        <textarea id="notes-{{ $variant }}" name="notes" rows="2" maxlength="2000" class="sa-input"></textarea>
                    </div>
                    <label class="md:col-span-2 flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_required" value="1">
                        به‌روزرسانی اجباری (نسخه‌های قدیمی‌تر تا به‌روز نشوند ادامه نمی‌دهند)
                    </label>
                    @if ($errors->any() && old('variant') === $variant)
                        <div class="md:col-span-2 text-sm" style="color: var(--sa-danger);">
                            @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
                        </div>
                    @endif
                    <div class="md:col-span-2"><button type="submit" class="sa-btn">آپلود و انتشار</button></div>
                </form>

                <div class="sa-table-wrap overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead>
                            <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                                <th class="py-2 px-2">نسخه</th>
                                <th class="py-2 px-2">حجم</th>
                                <th class="py-2 px-2">تاریخ</th>
                                <th class="py-2 px-2">دانلود</th>
                                <th class="py-2 px-2">وضعیت</th>
                                <th class="py-2 px-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($releases as $release)
                                <tr style="border-bottom: 1px solid var(--sa-border);" data-app-release="{{ $release->id }}">
                                    <td class="py-2 px-2"><span dir="ltr">{{ $release->version_name }}</span> <span style="color: var(--sa-text-dim);">({{ to_persian_num((string) $release->version_code) }})</span>
                                        @if ($release->is_required)<span class="sa-badge sa-badge-bad">اجباری</span>@endif
                                    </td>
                                    <td class="py-2 px-2">{{ $release->sizeLabel() }}</td>
                                    <td class="py-2 px-2" title="{{ $release->uploader?->name }}">{{ \Morilog\Jalali\Jalalian::fromCarbon($release->created_at)->format('Y/m/d H:i') }}</td>
                                    <td class="py-2 px-2">{{ to_persian_num((string) $release->download_count) }}</td>
                                    <td class="py-2 px-2">
                                        <form method="POST" action="{{ route('superadmin.app-releases.update', $release) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_published" value="{{ $release->is_published ? 0 : 1 }}">
                                            <button type="submit" style="color: {{ $release->is_published ? 'var(--sa-success)' : 'var(--sa-text-dim)' }};">
                                                {{ $release->is_published ? 'منتشر — پنهان کن' : 'پنهان — منتشر کن' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-2 px-2">
                                        <form method="POST" action="{{ route('superadmin.app-releases.destroy', $release) }}" onsubmit="return confirm('این نسخه و فایلش حذف شود؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="color: var(--sa-danger);">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr><td colspan="6" class="pb-2 px-2 text-xs" dir="ltr" style="color: var(--sa-text-dim); text-align:left;">SHA-256: {{ $release->sha256 }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-center" style="color: var(--sa-text-dim);">هنوز نسخه‌ای آپلود نشده؛ صفحه‌ی دانلود برای این اپ «به‌زودی» نشان می‌دهد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
