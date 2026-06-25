@extends('layouts.admin')
@section('title', 'جزئیات کاربر')

@push('styles')
    <style>
        .form-input { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:8px 12px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        {{-- هدر --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold"
                     style="background:var(--admin-accent); color:#fff;">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $user->name }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        @if($user->phone_verified_at)
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:#F0FDF4; color:#166534;">فعال</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:#FEF2F2; color:#991B1B;">غیرفعال</span>
                        @endif
                        @if($user->is_admin)
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:#F5F3FF; color:#7C3AED;">مدیر</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @permission('edit-users')
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-white"
                   style="background:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-accent-hover)'"
                   onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    ویرایش
                </a>
                @endpermission
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ستون راست: اطلاعات + نوبت‌ها --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- اطلاعات پایه --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات کاربر</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">شماره موبایل</p>
                            <p dir="ltr" class="font-medium" style="color:var(--admin-text);">{{ $user->phone }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">ایمیل</p>
                            <p dir="ltr" class="font-medium" style="color:var(--admin-text);">{{ $user->email ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">تاریخ عضویت</p>
                            <p class="persian-number font-medium" style="color:var(--admin-text);">{{ verta($user->created_at)->format('Y/m/d H:i') }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">آخرین بروزرسانی</p>
                            <p class="persian-number font-medium" style="color:var(--admin-text);">{{ verta($user->updated_at)->format('Y/m/d') }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="mb-2" style="color:var(--admin-text-dim);">نقش‌ها</p>
                            <div class="flex flex-wrap gap-2">
                                @forelse($user->roles as $role)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:var(--admin-accent-light); color:var(--admin-accent);">{{ $role->label }}</span>
                                @empty
                                    <span class="text-sm" style="color:var(--admin-text-light);">بدون نقش</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- نوبت‌های اخیر --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        آخرین نوبت‌ها
                    </div>
                    @if($bookings->isEmpty())
                        <p class="px-4 py-8 text-center text-sm" style="color:var(--admin-text-dim);">هیچ نوبتی ثبت نشده</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                <tr style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                                    <th class="px-4 py-2.5 text-right font-medium">#</th>
                                    <th class="px-4 py-2.5 text-right font-medium">تاریخ</th>
                                    <th class="px-4 py-2.5 text-right font-medium">خدمت</th>
                                    <th class="px-4 py-2.5 text-right font-medium">متخصص</th>
                                    <th class="px-4 py-2.5 text-right font-medium">وضعیت</th>
                                    <th class="px-4 py-2.5 text-right font-medium">مبلغ</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bookings as $booking)
                                    @php
                                        $sm=['pending'=>['در انتظار','#FFFBEB','#92400E'],'confirmed'=>['تایید','#F0FDF4','#166534'],'completed'=>['انجام شده','#EFF6FF','#1D4ED8'],'cancelled'=>['لغو','#FEF2F2','#991B1B']];
                                        $bs=$sm[$booking->status]??[$booking->status,'#F1F5F9','#475569'];
                                    @endphp
                                    <tr style="border-top:1px solid var(--admin-border);"
                                        onmouseover="this.style.background='var(--admin-accent-light)'"
                                        onmouseout="this.style.background=''">
                                        <td class="px-4 py-2.5 persian-number" style="color:var(--admin-text-dim);">{{ $booking->id }}</td>
                                        <td class="px-4 py-2.5 persian-number" style="color:var(--admin-text);">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</td>
                                        <td class="px-4 py-2.5" style="color:var(--admin-text);">{{ $booking->service?->name ?? '—' ?? '—' }}</td>
                                        <td class="px-4 py-2.5" style="color:var(--admin-text-dim);">{{ $booking->specialist?->name ?? '—' ?? '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:{{ $bs[1] }}; color:{{ $bs[2] }};">{{ $bs[0] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 persian-number" style="color:var(--admin-text);">{{ number_format($booking->prepayment_amount) }} ت</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 py-2.5 text-center" style="border-top:1px solid var(--admin-border);">
                            <a href="{{ route('admin.bookings.index', ['user_id' => $user->id]) }}"
                               class="text-xs" style="color:var(--admin-accent);">مشاهده همه نوبت‌ها</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ستون چپ: عملیات --}}
            <div class="space-y-5">

                {{-- عملیات --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">عملیات</h2>
                    <div class="space-y-2">
                        @permission('edit-users')
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm transition-colors"
                           style="background:var(--admin-accent-light); color:var(--admin-accent);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            ویرایش اطلاعات
                        </a>
                        <form action="{{ route('admin.users.status.update', $user) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="is_active" value="{{ $user->phone_verified_at ? 0 : 1 }}">
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm transition-colors"
                                    style="background:{{ $user->phone_verified_at ? '#FEF2F2' : '#F0FDF4' }}; color:{{ $user->phone_verified_at ? '#991B1B' : '#166534' }};"
                                    onmouseover="this.style.opacity='0.8'"
                                    onmouseout="this.style.opacity='1'">
                                @if($user->phone_verified_at)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                    غیرفعال کردن
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    فعال کردن
                                @endif
                            </button>
                        </form>
                        @endpermission
                        @permission('delete-users')
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="button" data-confirm-delete data-confirm-message="آیا از حذف {{ $user->name }} اطمینان دارید؟"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm transition-colors"
                                    style="background:#FEF2F2; color:#991B1B;"
                                    onmouseover="this.style.background='#FEE2E2'"
                                    onmouseout="this.style.background='#FEF2F2'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                                حذف کاربر
                            </button>
                        </form>
                        @endpermission
                    </div>
                </div>

                {{-- تغییر رمز عبور --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تغییر رمز عبور</h2>
                    <form action="{{ route('admin.users.password.reset', $user) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">رمز عبور جدید</label>
                                <input type="password" name="password" dir="ltr" placeholder="حداقل ۸ کاراکتر"
                                       class="form-input">
                                @error('password') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">تکرار رمز عبور</label>
                                <input type="password" name="password_confirmation" dir="ltr" placeholder="تکرار رمز عبور"
                                       class="form-input">
                            </div>
                            <button type="submit"
                                    class="w-full py-2 rounded-lg text-sm font-medium text-white transition-colors"
                                    style="background:var(--admin-accent);"
                                    onmouseover="this.style.background='var(--admin-accent-hover)'"
                                    onmouseout="this.style.background='var(--admin-accent)'">
                                تغییر رمز عبور
                            </button>
                        </div>
                    </form>
                </div>

                {{-- مدیریت نقش‌ها --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">مدیریت نقش‌ها</h2>
                    <form action="{{ route('admin.users.roles.sync', $user) }}" method="POST">
                        @csrf
                        <div class="space-y-2 mb-4">
                            @foreach($roles as $role)
                                <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer text-sm"
                                       style="color:var(--admin-text);"
                                       onmouseover="this.style.background='var(--admin-accent-light)'"
                                       onmouseout="this.style.background=''">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                           style="accent-color:var(--admin-accent); width:15px; height:15px;"
                                        {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                                    <span>{{ $role->label }}</span>
                                    <span class="text-xs mr-auto" style="color:var(--admin-text-light);">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit"
                                class="w-full py-2 rounded-lg text-sm font-medium text-white transition-colors"
                                style="background:var(--admin-accent);"
                                onmouseover="this.style.background='var(--admin-accent-hover)'"
                                onmouseout="this.style.background='var(--admin-accent)'">
                            بروزرسانی نقش‌ها
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
