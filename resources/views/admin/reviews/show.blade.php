@extends('layouts.admin')
@section('title', 'جزئیات نظر')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">جزئیات نظر</h1>
            <a href="{{ route('admin.reviews.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{--Comment details--}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Points --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">امتیازها</h2>
                    @php $r = $review->overall_rating ?? 0; @endphp
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-4xl font-bold persian-number" style="color:{{ $r>=4 ? '#16A34A' : ($r>=3 ? '#D97706' : '#DC2626') }};">{{ $r }}</span>
                        <div>
                            <div class="flex gap-1 mb-1">
                                @for($i=1;$i<=5;$i++)
                                    <svg class="w-5 h-5" fill="{{ $i<=$r ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                                         style="color:{{ $i<=$r ? '#F59E0B' : 'var(--admin-border)' }};">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-xs" style="color:var(--admin-text-dim);">امتیاز کلی</p>
                        </div>
                    </div>

                    @if($review->comment)
                        <div class="p-4 rounded-lg text-sm leading-relaxed" style="background:var(--admin-accent-light); color:var(--admin-text);">
                            <p class="font-medium mb-2 text-xs" style="color:var(--admin-text-dim);">متن نظر:</p>
                            {{ $review->comment }}
                        </div>
                    @endif
                </div>

                {{-- Appointment information --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات نوبت مرتبط</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">مشتری</p>
                            <p class="font-medium" style="color:var(--admin-text);">{{ $review->user->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">متخصص</p>
                            <p class="font-medium" style="color:var(--admin-text);">{{ $review->specialist->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">خدمت</p>
                            <p class="font-medium" style="color:var(--admin-text);">{{ $review->service->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">تاریخ ثبت نظر</p>
                            <p class="persian-number font-medium" style="color:var(--admin-text);">{{ verta($review->reviewed_at ?? $review->created_at)->format('Y/m/d H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Operation --}}
            <div class="space-y-4">
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">وضعیت و عملیات</h2>
                    <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                          style="background:{{ $review->is_approved ? '#F0FDF4' : '#FFFBEB' }}; color:{{ $review->is_approved ? '#166534' : '#92400E' }};">
                        {{ $review->is_approved ? 'تایید شده' : 'در انتظار تایید' }}
                    </span>
                        @if($review->is_featured)
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#EFF6FF; color:#1D4ED8;">ویژه</span>
                        @endif
                    </div>
                    <div class="space-y-2">
                        @if(!$review->is_approved)
                            <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-white"
                                        style="background:#16A34A;"
                                        onmouseover="this.style.background='#15803D'"
                                        onmouseout="this.style.background='#16A34A'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    تایید نظر
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.reviews.feature', $review->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium"
                                    style="background:#EFF6FF; color:#1D4ED8;"
                                    onmouseover="this.style.opacity='0.8'"
                                    onmouseout="this.style.opacity='1'">
                                <svg class="w-4 h-4" fill="{{ $review->is_featured ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                {{ $review->is_featured ? 'حذف از ویژه' : 'افزودن به ویژه' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="button"
                                    data-confirm-delete data-confirm-message="آیا از حذف این نظر اطمینان دارید؟"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium"
                                    style="background:#FEF2F2; color:#991B1B;"
                                    onmouseover="this.style.background='#FEE2E2'"
                                    onmouseout="this.style.background='#FEF2F2'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                                حذف نظر
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
