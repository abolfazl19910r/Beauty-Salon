{{--
    سابقه‌ی رضایت عکس دوره (۲۰۲۶-۱۰-۲۸): هر درخواست و تصمیم با زمان و منبع؛ ثبت کادر با برچسب «ثبت حضوری».
    $course، $dimStyle (رنگ کم‌رنگ هر پنل)، $showStaff (نام کارمند ثبت‌کننده/درخواست‌دهنده — فقط پنل‌های کادر).
--}}
@php($consentHistory = $course->photoConsents()->with(['recorder', 'requester'])->limit(20)->get())
@if ($consentHistory->isNotEmpty())
    <div class="mt-3 text-xs leading-6" style="{{ $dimStyle }}" data-photo-consent-history>
        <div class="font-bold mb-1">سابقه‌ی رضایت عکس</div>
        <ul class="space-y-1">
            @foreach ($consentHistory as $entry)
                @php($consentStaff = empty($showStaff) ? null : ($entry->source === 'in_person' ? $entry->recorder?->name : ($entry->status === \App\Models\CoursePhotoConsent::PENDING && $entry->requester ? 'درخواست: '.$entry->requester->name : null)))
                <li class="persian-number" data-consent-entry="{{ $entry->status }}">
                    {{ verta($entry->decided_at ?? $entry->requested_at)->format('Y/m/d H:i') }} — {{ $entry->label() }}{{ $consentStaff ? ' ('.$consentStaff.')' : '' }}
                    <span class="opacity-70">· نسخه‌ی متن {{ to_persian_num(str_replace('-', '/', $entry->text_version)) }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
