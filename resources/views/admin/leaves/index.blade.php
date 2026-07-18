@extends('layouts.admin')
@section('title', 'مرخصی‌ها')

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">مرخصی‌ها</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">تمام درخواست‌های مرخصی همه‌ی متخصصین</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            @php
                $statusTabs = [
                    ''         => 'همه',
                    'pending'  => 'در انتظار',
                    'approved' => 'تایید شده',
                    'rejected' => 'رد شده',
                ];
            @endphp
            @foreach($statusTabs as $value => $label)
                <a href="{{ route('admin.leaves.index', $value ? ['status' => $value] : []) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                   style="{{ request('status', '') === $value
                    ? 'background:var(--admin-accent); color:#fff;'
                    : 'background:var(--admin-accent-light); color:var(--admin-text-dim);' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ شروع</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ پایان</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">دلیل</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">نام متخصص</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($leaves as $leave)
                        <tr style="border-bottom:1px solid var(--admin-border);">
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text);">{{ verta($leave->start_date)->format('Y/m/d') }}</td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text);">{{ verta($leave->end_date)->format('Y/m/d') }}</td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim); max-width:220px;">
                                {{ $leave->reason ?: '—' }}
                                @if($leave->status === 'rejected' && $leave->reject_reason)
                                    <p class="text-xs mt-1" style="color:#DC2626;">دلیل رد: {{ $leave->reject_reason }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($leave->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#FFFBEB; color:#92400E;">در انتظار</span>
                                @elseif($leave->status === 'approved')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">تایید شده</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#FEF2F2; color:#991B1B;">رد شده</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.specialists.show', $leave->specialist_id) }}"
                                   class="font-medium hover:underline" style="color:var(--admin-accent);">
                                    {{ $leave->specialist?->name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if($leave->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.leaves.update', $leave) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" title="تایید"
                                                    class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                    style="color:#16A34A;"
                                                    onmouseover="this.style.background='#F0FDF4'"
                                                    onmouseout="this.style.background=''">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </form>
                                        <button type="button" title="رد"
                                                onclick="openRejectModal('{{ route('admin.leaves.update', $leave) }}')"
                                                class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                style="color:#DC2626;"
                                                onmouseover="this.style.background='#FEF2F2'"
                                                onmouseout="this.style.background=''">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <span style="color:var(--admin-text-light);">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center" style="color:var(--admin-text-dim);">
                                هیچ درخواست مرخصی‌ای یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($leaves->hasPages())
                <div class="p-4" style="border-top:1px solid var(--admin-border);">
                    {{ $leaves->links() }}
                </div>
            @endif
        </div>

        {{-- Reject reason modal --}}
        <div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center"
             style="background:rgba(15,23,42,0.5);"
             onclick="if(event.target===this)document.getElementById('reject-modal').classList.add('hidden')">
            <div class="rounded-xl shadow-xl w-full max-w-md mx-4 fade-in" style="background:var(--admin-surface);">
                <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--admin-border);">
                    <h2 class="text-base font-bold" style="color:#DC2626;">رد درخواست مرخصی</h2>
                    <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                            class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                            style="color:var(--admin-text-dim);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <form id="reject-form" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="rejected">
                    <div class="p-5">
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text-dim);">دلیل رد (الزامی)</label>
                        <textarea name="reject_reason" rows="3" required class="form-input" placeholder="دلیل رد درخواست مرخصی را بنویسید..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-4" style="border-top:1px solid var(--admin-border);">
                        <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                                class="px-4 py-2 rounded-lg text-sm transition-colors"
                                style="background:var(--admin-accent-light); color:var(--admin-text-dim);">انصراف</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors" style="background:#DC2626;">رد درخواست</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openRejectModal(actionUrl) {
                document.getElementById('reject-form').action = actionUrl;
                document.getElementById('reject-modal').classList.remove('hidden');
            }
        </script>
    </div>
@endsection
