{{-- جلسه‌ی دوره‌ی درمانی روی صفحه‌ی نوبت متخصص (بسته‌ی ب): برچسب، جلسه‌های قبلی با یادداشت/عکس، یادداشت و عکس همین جلسه --}}
@php
    $course = $booking->customerCourse;
    $myNote = $courseRecords['notes'][$booking->id] ?? null;
    $myPhotos = $courseRecords['photos'][$booking->id] ?? collect();
    $field = 'background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);';
@endphp
<div class="specialist-card p-6" data-course-session-panel>
    @if (session('success'))
        <div class="mb-3 p-3 rounded-lg text-sm bg-m-ok/10 text-m-ok">{{ session('success') }}</div>
    @endif
    @if (session('error') || $errors->any())
        <div class="mb-3 p-3 rounded-lg text-sm bg-m-bad/10 text-m-bad">{{ session('error') ?? $errors->first() }}</div>
    @endif

    <div class="flex justify-between items-center flex-wrap gap-2">
        <h3 class="font-bold text-[var(--specialist-plum-light)] font-serif-fa">{{ $course->sessionLabel((int) $booking->course_session_number) }} — {{ $course->name }}</h3>
        <span class="text-xs text-[var(--specialist-text-dim)] persian-number">انجام‌شده {{ to_persian_num((string) $course->sessions_used) }} از {{ to_persian_num((string) $course->sessions_total) }}</span>
    </div>

    @if ($courseSessions->where('id', '!=', $booking->id)->isNotEmpty())
        <div class="mt-4 space-y-3">
            <div class="text-sm text-[var(--specialist-plum-muted)]">جلسه‌های دیگر این دوره</div>
            @foreach ($courseSessions->where('id', '!=', $booking->id) as $session)
                @php $note = $courseRecords['notes'][$session->id] ?? null; @endphp
                <div class="rounded-lg p-3 text-sm" style="{{ $field }}">
                    <div class="text-[var(--specialist-text-dim)] persian-number">{{ $session->course_session_number ? $course->sessionLabel($session->course_session_number) : '' }} — {{ verta($session->booking_time)->format('Y/m/d') }} · {{ $session->specialist?->name }} · {{ $session->status === 'completed' ? 'انجام شد' : ($session->status === 'cancelled' ? 'لغو' : 'رزرو') }}</div>
                    @if ($note?->note)<p class="mt-1 text-[var(--specialist-text)] leading-7">{{ $note->note }}</p>@endif
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($courseRecords['photos'][$session->id] ?? [] as $photo)
                            <a href="{{ route('specialist.course-photos.show', $photo) }}" target="_blank"><img src="{{ route('specialist.course-photos.show', $photo) }}" class="w-14 h-14 object-cover rounded" alt="" loading="lazy"></a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($booking->status !== 'cancelled')
        <form method="POST" action="{{ route('specialist.bookings.course-note', $booking) }}" class="mt-5">
            @csrf @method('PUT')
            <label class="block text-sm text-[var(--specialist-plum-muted)] mb-1">یادداشت این جلسه</label>
            <textarea name="note" rows="3" maxlength="2000" class="w-full rounded-lg p-3 text-[var(--specialist-text)]" style="{{ $field }}" placeholder="ناحیه، تنظیمات دستگاه، واکنش پوست…">{{ $myNote?->note }}</textarea>
            <div class="flex justify-between items-center mt-2">
                <label class="text-xs text-[var(--specialist-text-dim)] flex items-center gap-1"><input type="checkbox" name="visible_to_customer" value="1" @checked($myNote?->visible_to_customer)> مشتری هم ببیند</label>
                <button type="submit" class="specialist-cta px-4 py-2 rounded-lg text-sm font-bold">ذخیره‌ی یادداشت</button>
            </div>
        </form>

        <div class="mt-5">
            <div class="text-sm text-[var(--specialist-plum-muted)] mb-2">عکس قبل/بعد</div>
            @if (! $course->hasPhotoConsent())
                @php($consentState = app(\App\Services\Courses\PhotoConsentService::class)->state($course))
                {{-- بسته‌ی مجوز 5د: متن رضایت (ماده‌ی ۵۸) --}}
                <p class="text-xs leading-6 mb-2 p-3 rounded-lg text-[var(--specialist-text-dim)]" style="border: 1px solid var(--specialist-border);" data-photo-consent-text>{{ \App\Support\Courses\PhotoConsent::TEXT }}</p>
                {{-- ۲۰۲۶-۱۰-۲۸: راه اول، خود مشتری در اپ، سایت سالن یا لینک پیامک تأیید می‌کند --}}
                <div class="flex flex-wrap items-center gap-2 mb-3" data-photo-consent-request>
                    @if ($consentState['open_request'])
                        <span class="text-xs text-[var(--specialist-text-dim)]">در انتظار پاسخ مشتری (فرستاده‌شده {{ verta($consentState['open_request']->last_sent_at)->format('Y/m/d H:i') }})</span>
                        @if ($consentState['can_resend'])
                            <form method="POST" action="{{ route('specialist.bookings.course-consent.resend', $booking) }}">@csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs text-[var(--specialist-text)]" style="border: 1px solid var(--specialist-border);">ارسال دوباره</button>
                            </form>
                        @endif
                    @elseif ($consentState['can_request'])
                        <form method="POST" action="{{ route('specialist.bookings.course-consent.request', $booking) }}">@csrf
                            <button type="submit" class="specialist-cta px-4 py-2 rounded-lg text-sm font-bold">درخواست رضایت از مشتری</button>
                        </form>
                    @else
                        <span class="text-xs text-[var(--specialist-text-dim)]">{{ $consentState['request_blocked_reason'] }}</span>
                    @endif
                </div>
                {{-- راه دوم: ثبت حضوری (در سابقه «ثبت حضوری») --}}
                <form method="POST" action="{{ route('specialist.bookings.course-consent', $booking) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm text-[var(--specialist-text)]" style="border: 1px solid var(--specialist-border);" onclick="return confirm(@js(\App\Support\Courses\PhotoConsent::STAFF_CONFIRM));">ثبت حضوری رضایت مشتری</button>
                </form>
            @else
                <div class="flex flex-wrap gap-2 items-end">
                    @foreach ($myPhotos as $photo)
                        <a href="{{ route('specialist.course-photos.show', $photo) }}" target="_blank" class="text-center text-xs text-[var(--specialist-text-dim)]"><img src="{{ route('specialist.course-photos.show', $photo) }}" class="w-16 h-16 object-cover rounded" alt="" loading="lazy">{{ \App\Models\CourseSessionPhoto::KINDS[$photo->kind] }}</a>
                    @endforeach
                    @if ($myPhotos->count() < \App\Models\CourseSessionPhoto::MAX_PER_SESSION)
                        <form method="POST" action="{{ route('specialist.bookings.course-photos.store', $booking) }}" enctype="multipart/form-data" class="flex items-center gap-2 text-xs">
                            @csrf
                            <select name="kind" class="rounded px-2 py-1 text-[var(--specialist-text)]" style="{{ $field }}"><option value="before">قبل</option><option value="after">بعد</option></select>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" class="text-xs text-[var(--specialist-text-dim)]">
                            <button type="submit" class="specialist-cta px-3 py-1 rounded">بارگذاری</button>
                        </form>
                    @endif
                </div>
            @endif
            @include('courses._consent-history', ['course' => $course, 'dimStyle' => 'color: var(--specialist-text-dim);', 'showStaff' => true])
        </div>
    @endif
</div>
