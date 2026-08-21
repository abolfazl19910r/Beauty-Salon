@extends('layouts.admin')
@section('title', 'مدیریت امتیاز کاربران')

@section('content')
    <div class="container px-6 mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                مدیریت امتیاز کاربران
            </h1>
            <a href="{{ route('admin.loyalty.index') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border transition-colors"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت به امتیازات
            </a>
        </div>

        {{-- Search --}}
        <div class="rounded-xl p-6 mb-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <form action="{{ route('admin.loyalty.points.index') }}" method="GET" class="flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو با نام یا شماره تلفن..."
                       class="flex-1 rounded-lg px-3 py-2 text-sm"
                       style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white rounded-lg"
                        style="background:var(--admin-accent)">
                    جستجو
                </button>
            </form>

            @if(request()->filled('search'))
                <div class="mt-4">
                    @if($users->isEmpty())
                        <p class="text-sm" style="color:var(--admin-text-dim)">هیچ کاربری با این مشخصات یافت نشد.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-right">
                                <thead>
                                <tr style="border-bottom:1px solid var(--admin-border)">
                                    <th class="p-3 font-medium" style="color:var(--admin-text-dim)">نام</th>
                                    <th class="p-3 font-medium" style="color:var(--admin-text-dim)">شماره تلفن</th>
                                    <th class="p-3 font-medium" style="color:var(--admin-text-dim)">عملیات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $u)
                                    <tr style="border-bottom:1px solid var(--admin-border)">
                                        <td class="p-3" style="color:var(--admin-text)">{{ $u->name }}</td>
                                        <td class="p-3" style="color:var(--admin-text)">{{ $u->phone }}</td>
                                        <td class="p-3">
                                            <a href="{{ route('admin.loyalty.points.index', ['user_id' => $u->id]) }}"
                                               class="text-xs font-medium" style="color:var(--admin-accent)">
                                                مشاهده و مدیریت امتیاز
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if($selectedUser && $pointsData)
            {{-- Selected user summary --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php
                    $cards = [
                        ['label' => 'کاربر', 'value' => $selectedUser->name . ' (' . $selectedUser->phone . ')'],
                        ['label' => 'مجموع کسب‌شده', 'value' => number_format($pointsData['total_earned'])],
                        ['label' => 'مجموع مصرف‌شده', 'value' => number_format($pointsData['total_spent'])],
                        ['label' => 'موجودی فعلی', 'value' => number_format($pointsData['current_balance'])],
                    ];
                @endphp
                @foreach($cards as $card)
                    <div class="rounded-xl p-5" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                        <p class="text-xs mb-1" style="color:var(--admin-text-dim)">{{ $card['label'] }}</p>
                        <p class="text-lg font-bold" style="color:var(--admin-text)">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            @if($pointsData['expiring_soon'] > 0)
                <div class="rounded-xl p-4 mb-6 text-sm" style="background:#fffbeb;color:#92400e;border:1px solid #fde68a">
                    {{ number_format($pointsData['expiring_soon']) }} امتیاز این کاربر تا ۳۰ روز آینده منقضی می‌شود.
                </div>
            @endif

            {{-- Add / Deduct forms --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                    <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">افزودن امتیاز</h3>
                    <form action="{{ route('admin.loyalty.points.add', $selectedUser) }}" method="POST">
                        @csrf
                        <input type="hidden" name="redirect_user_id" value="{{ $selectedUser->id }}">
                        <div class="mb-3">
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">تعداد امتیاز</label>
                            <input type="number" name="points" min="1" required value="{{ old('points') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('points')
                                <p class="text-xs mt-1" style="color:#dc2626">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">توضیح / دلیل</label>
                            <input type="text" name="description" required value="{{ old('description') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('description')
                                <p class="text-xs mt-1" style="color:#dc2626">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">تاریخ انقضا (اختیاری)</label>
                            <input type="date" name="expires_at" value="{{ old('expires_at') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('expires_at')
                                <p class="text-xs mt-1" style="color:#dc2626">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg"
                                style="background:#16a34a">
                            افزودن امتیاز
                        </button>
                    </form>
                </div>

                <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                    <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">کسر امتیاز</h3>
                    <form action="{{ route('admin.loyalty.points.deduct', $selectedUser) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">تعداد امتیاز</label>
                            <input type="number" name="points" min="1" required value="{{ old('points') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('points')
                                <p class="text-xs mt-1" style="color:#dc2626">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">توضیح / دلیل</label>
                            <input type="text" name="description" required value="{{ old('description') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('description')
                                <p class="text-xs mt-1" style="color:#dc2626">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" data-confirm-action
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg"
                                style="background:#dc2626">
                            کسر امتیاز
                        </button>
                    </form>
                </div>
            </div>

            {{-- History --}}
            <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <div class="p-6" style="border-bottom:1px solid var(--admin-border)">
                    <h3 class="text-base font-semibold" style="color:var(--admin-text)">تاریخچه‌ی امتیازات این کاربر</h3>
                </div>

                @if($pointsData['history']->isEmpty())
                    <div class="p-10 text-center text-sm" style="color:var(--admin-text-dim)">
                        هیچ تراکنش امتیازی برای این کاربر ثبت نشده است.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-right">
                            <thead>
                            <tr style="border-bottom:1px solid var(--admin-border)">
                                <th class="p-4 font-medium" style="color:var(--admin-text-dim)">تاریخ</th>
                                <th class="p-4 font-medium" style="color:var(--admin-text-dim)">نوع</th>
                                <th class="p-4 font-medium" style="color:var(--admin-text-dim)">مقدار</th>
                                <th class="p-4 font-medium" style="color:var(--admin-text-dim)">توضیح</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pointsData['history'] as $point)
                                <tr style="border-bottom:1px solid var(--admin-border)">
                                    <td class="p-4" style="color:var(--admin-text)">{{ $point->created_at->format('Y/m/d H:i') }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $point->type === 'earned' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $point->type === 'earned' ? 'کسب‌شده' : 'مصرف‌شده' }}
                                        </span>
                                    </td>
                                    <td class="p-4" style="color:var(--admin-text)">{{ $point->points > 0 ? '+' : '' }}{{ number_format($point->points) }}</td>
                                    <td class="p-4" style="color:var(--admin-text)">{{ $point->description }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">
                        {{ $pointsData['history']->links() }}
                    </div>
                @endif
            </div>
        @elseif($selectedUser === null && request()->filled('user_id'))
            <div class="rounded-xl p-6 text-sm" style="background:var(--admin-surface);border:1px solid var(--admin-border);color:var(--admin-text-dim)">
                کاربر مورد نظر یافت نشد.
            </div>
        @endif

    </div>
@endsection
