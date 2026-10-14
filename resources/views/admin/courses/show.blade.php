@extends('layouts.admin')
@section('title', 'دوره‌ی '.$course->name)

@section('content')
    @php
        $statusLabels = ['pending' => 'منتظر تایید', 'confirmed' => 'تایید شده', 'completed' => 'انجام شد', 'cancelled' => 'لغو شد', 'pending_payment' => 'در انتظار پرداخت'];
        $open = ! in_array($course->status, ['cancelled', 'completed'], true);
    @endphp
    <div class="fade-in" style="max-width: 64rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <a href="{{ route('admin.courses.index') }}" class="text-sm" style="{{ $dim }}">→ دوره‌ها</a>

        <div class="rounded-xl p-5 mt-3 mb-5" style="{{ $box }}">
            <div class="flex justify-between items-start flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $course->name }}</h1>
                    <p class="text-sm mt-1" style="{{ $dim }}">@if ($course->user)<a href="{{ route('admin.customers.show', $course->user_id) }}" style="color:var(--admin-accent);">{{ $course->user->name }}</a>@endif <span dir="ltr">{{ $course->user?->phone }}</span> — {{ $course->service?->name }}@if ($course->specialist) · فقط {{ $course->specialist->name }}@endif</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm" style="background:var(--admin-accent-light); color:var(--admin-accent);">{{ $course->statusLabel() }}</span>
            </div>
            <div class="mt-4 h-3 rounded-full overflow-hidden" style="background:var(--admin-accent-light);">
                <div class="h-full" style="width: {{ round($course->sessions_used * 100 / max(1, $course->sessions_total)) }}%; background:var(--admin-accent);"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4 text-sm persian-number">
                <div><div style="{{ $dim }}">انجام/سوخت</div><div class="font-bold">{{ to_persian_num((string) $course->sessions_used) }} از {{ to_persian_num((string) $course->sessions_total) }}@if ($course->sessions_burned) <span class="text-xs" style="color:#b45309;">({{ to_persian_num((string) $course->sessions_burned) }} سوخت)</span>@endif</div></div>
                <div><div style="{{ $dim }}">پرداخت‌شده</div><div class="font-bold">{{ number_format($course->amount_paid) }} از {{ number_format($course->price_total) }}</div></div>
                <div><div style="{{ $dim }}">ارزش هر جلسه</div><div class="font-bold">{{ number_format($course->per_session_value) }}</div></div>
                <div><div style="{{ $dim }}">موعد جلسه‌ی بعد</div><div class="font-bold">{{ $course->next_due_at && $course->status === 'active' ? verta($course->next_due_at)->format('Y/m/d') : '—' }}</div></div>
                <div><div style="{{ $dim }}">مهلت تا</div><div class="font-bold">{{ $course->expires_at ? verta($course->expires_at)->format('Y/m/d') : 'بی‌مهلت' }}</div></div>
            </div>
            @if ($course->discount_amount > 0)
                <p class="text-xs mt-3 persian-number" style="{{ $dim }}">قیمت بسته {{ number_format($course->list_price) }} با کد «{{ $course->discount_code }}» ({{ number_format($course->discount_amount) }} تخفیف).</p>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            @if ($open)
                <form method="POST" action="{{ route('admin.courses.sessions.store', $course) }}" class="rounded-xl p-5" style="{{ $box }}">
                    @csrf
                    <h2 class="font-bold mb-1" style="color:var(--admin-text);">رزرو جلسه</h2>
                    @if ($blocker)
                        <p class="text-sm" style="color:#b45309;">{{ $blocker }}</p>
                    @else
                        <p class="text-xs mb-3" style="{{ $dim }}">زودترین روز طبق فاصله‌ی حداقل: {{ verta($course->earliestNextSession())->format('Y/m/d') }} (مدیر می‌تواند زودتر بگذارد).</p>
                        <div class="grid grid-cols-3 gap-2">
                            <select name="specialist_id" class="{{ $input }}" style="{{ $inputStyle }}">
                                @foreach ($specialists as $specialist)
                                    <option value="{{ $specialist->id }}">{{ $specialist->name }}</option>
                                @endforeach
                            </select>
                            <input name="date" dir="ltr" placeholder="1405/08/10" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('date') }}">
                            <input name="time" type="time" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('time', '10:00') }}">
                        </div>
                        <button type="submit" class="mt-3 px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">رزرو</button>
                    @endif
                </form>

                <form method="POST" action="{{ route('admin.courses.payments.store', $course) }}" class="rounded-xl p-5" style="{{ $box }}">
                    @csrf
                    <h2 class="font-bold mb-1" style="color:var(--admin-text);">ثبت پرداخت حضوری</h2>
                    <p class="text-xs mb-3 persian-number" style="{{ $dim }}">مانده: {{ number_format($course->balanceDue()) }} تومان@if ($course->status === 'pending_payment') — حداقل پرداخت اول {{ number_format($course->min_prepayment) }}@endif</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input name="amount" dir="ltr" inputmode="numeric" class="{{ $input }}" style="{{ $inputStyle }}" placeholder="مبلغ (تومان)" value="{{ $course->balanceDue() > 0 ? (int) $course->balanceDue() : '' }}">
                        <input name="note" class="{{ $input }}" style="{{ $inputStyle }}" placeholder="یادداشت (اختیاری)">
                    </div>
                    <button type="submit" class="mt-3 px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}" @disabled($course->balanceDue() <= 0)>ثبت پرداخت</button>
                </form>
            @endif
        </div>

        <div class="rounded-xl p-5 mb-5" style="{{ $box }}">
            <div class="flex justify-between items-center flex-wrap gap-2 mb-3">
                <h2 class="font-bold" style="color:var(--admin-text);">جلسه‌ها</h2>
                @if ($course->hasPhotoConsent())
                    <span class="text-xs" style="{{ $dim }}">رضایت عکس: {{ verta($course->photo_consent_at)->format('Y/m/d') }}</span>
                @else
                    <form method="POST" action="{{ route('admin.courses.consent', $course) }}">
                        @csrf
                        <button type="submit" class="text-xs px-3 py-1 rounded-lg" style="{{ $ghost }}" data-confirm-action="مشتری رضایت داده که از جلسه‌ها عکس قبل/بعد گرفته شود؟">ثبت رضایت مشتری برای عکس</button>
                    </form>
                @endif
            </div>
            @forelse ($bookings as $booking)
                <div class="py-4 {{ $loop->last ? '' : 'border-b' }}" style="border-color:var(--admin-border);" data-session="{{ $booking->id }}">
                    <div class="flex justify-between text-sm persian-number flex-wrap gap-2">
                        <a href="{{ route('admin.bookings.show', $booking) }}" style="color:var(--admin-accent);">{{ $booking->course_session_number ? $course->sessionLabel($booking->course_session_number) : 'جلسه' }} — {{ verta($booking->booking_time)->format('Y/m/d') }} {{ $booking->booking_time->format('H:i') }} · {{ $booking->specialist?->name }}</a>
                        <span style="{{ $dim }}">{{ $booking->course_session_burned ? 'سوخت (لغو دیرهنگام)' : ($statusLabels[$booking->status] ?? $booking->status) }}</span>
                    </div>
                    @if ($booking->status !== 'cancelled')
                        <form method="POST" action="{{ route('admin.courses.sessions.note', [$course, $booking]) }}" class="mt-2">
                            @csrf @method('PUT')
                            <textarea name="note" rows="2" maxlength="2000" class="{{ $input }}" style="{{ $inputStyle }}" placeholder="یادداشت جلسه: ناحیه، تنظیمات دستگاه، واکنش پوست…">{{ $notes[$booking->id]->note ?? '' }}</textarea>
                            <div class="flex items-center justify-between mt-1">
                                <label class="text-xs flex items-center gap-1"><input type="checkbox" name="visible_to_customer" value="1" @checked($notes[$booking->id]->visible_to_customer ?? false)> مشتری هم ببیند</label>
                                <button type="submit" class="text-xs px-3 py-1 rounded-lg" style="{{ $ghost }}">ذخیره‌ی یادداشت</button>
                            </div>
                        </form>
                        <div class="flex flex-wrap gap-2 mt-2 items-end">
                            @foreach ($photos[$booking->id] ?? [] as $photo)
                                <div class="text-center">
                                    <a href="{{ route('admin.courses.photos.show', [$course, $photo]) }}" target="_blank"><img src="{{ route('admin.courses.photos.show', [$course, $photo]) }}" class="w-16 h-16 object-cover rounded" alt="" loading="lazy"></a>
                                    <form method="POST" action="{{ route('admin.courses.photos.destroy', [$course, $photo]) }}">@csrf @method('DELETE')<button type="submit" data-confirm-delete class="text-xs" style="color:#b91c1c;">{{ \App\Models\CourseSessionPhoto::KINDS[$photo->kind] }} ×</button></form>
                                </div>
                            @endforeach
                            @if ($course->hasPhotoConsent() && count($photos[$booking->id] ?? []) < \App\Models\CourseSessionPhoto::MAX_PER_SESSION)
                                <form method="POST" action="{{ route('admin.courses.sessions.photos.store', [$course, $booking]) }}" enctype="multipart/form-data" class="flex items-center gap-1 text-xs">
                                    @csrf
                                    <select name="kind" class="rounded px-1 py-1" style="{{ $inputStyle }}"><option value="before">قبل</option><option value="after">بعد</option></select>
                                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="text-xs">
                                    <button type="submit" class="px-2 py-1 rounded" style="{{ $ghost }}">بارگذاری</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm" style="{{ $dim }}">هنوز جلسه‌ای رزرو نشده است.</p>
            @endforelse
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            <div class="rounded-xl p-5" style="{{ $box }}">
                <h2 class="font-bold mb-3" style="color:var(--admin-text);">پرداخت‌ها</h2>
                @forelse ($course->payments->sortByDesc('id') as $payment)
                    <div class="flex justify-between text-sm py-1 persian-number">
                        <span>{{ verta($payment->created_at)->format('Y/m/d H:i') }} — {{ $payment->methodLabel() }}@if ($payment->recordedBy) · {{ $payment->recordedBy->name }}@endif</span>
                        <span>{{ number_format($payment->amount) }}</span>
                    </div>
                @empty
                    <p class="text-sm" style="{{ $dim }}">پرداختی ثبت نشده است.</p>
                @endforelse
            </div>

            @if ($open && $course->status !== 'pending_payment')
                <div class="rounded-xl p-5 space-y-4" style="{{ $box }}">
                    <form method="POST" action="{{ route('admin.courses.extend', $course) }}">
                        @csrf @method('PUT')
                        <h2 class="font-bold mb-2" style="color:var(--admin-text);">تمدید مهلت</h2>
                        <div class="flex gap-2">
                            <input name="expires_at" dir="ltr" placeholder="1406/01/15" class="{{ $input }}" style="{{ $inputStyle }}">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">تمدید</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.courses.cancel', $course) }}">
                        @csrf
                        <h2 class="font-bold mb-2" style="color:#b91c1c;">لغو دوره و برگشت پول</h2>
                        <p class="text-xs mb-2 persian-number" style="{{ $dim }}">پیشنهاد: {{ number_format($refundable) }} تومان (پرداخت‌شده − جلسه‌های مصرف‌شده × ارزش هر جلسه) به کیف پول مشتری. نوبت‌های آینده‌ی دوره هم لغو می‌شوند.</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="refund" dir="ltr" inputmode="numeric" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ (int) $refundable }}">
                            <input name="reason" class="{{ $input }}" style="{{ $inputStyle }}" placeholder="دلیل">
                        </div>
                        <button type="submit" class="mt-2 px-4 py-2 rounded-lg text-sm font-bold" style="background:#b91c1c; color:#fff;" data-confirm-action="دوره لغو و مبلغ به کیف پول مشتری برگردانده شود؟">لغو دوره</button>
                    </form>
                </div>
            @endif
        </div>

        @if ($activity->isNotEmpty())
            <div class="rounded-xl p-5" style="{{ $box }}">
                <h2 class="font-bold mb-2" style="color:var(--admin-text);">تاریخچه</h2>
                @foreach ($activity as $entry)
                    <div class="text-xs py-1 persian-number" style="{{ $dim }}">{{ verta($entry->created_at)->format('Y/m/d H:i') }} — {{ ['course_cancelled' => 'لغو دوره', 'course_extended' => 'تمدید مهلت', 'course_photo_consent' => 'ثبت رضایت عکس', 'course_sold_in_person' => 'فروش حضوری', 'course_payment_in_person' => 'پرداخت حضوری'][$entry->description] ?? $entry->description }}@if ($entry->causer) · {{ $entry->causer->name ?? '' }}@endif</div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
