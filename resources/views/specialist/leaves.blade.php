@extends('layouts.specialist')

@section('title', 'مرخصی‌ها')

@php
    $leaveStatusMap = [
        'pending'  => ['label' => 'در انتظار تایید', 'class' => 'bg-amber-400/10 text-amber-300'],
        'approved' => ['label' => 'تایید شده',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'rejected' => ['label' => 'رد شده',          'class' => 'bg-red-500/10 text-red-300'],
    ];
@endphp

@section('content')
    <div class="fade-in max-w-6xl mx-auto space-y-6">

        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa mb-1">مدیریت مرخصی‌ها</h1>
                <p class="text-sm text-[var(--specialist-text-dim)]">درخواست و مشاهده مرخصی‌های خود</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('specialist.leaves.create') }}"
                   class="specialist-cta px-4 py-2 rounded-lg transition-opacity hover:opacity-90 flex items-center text-sm font-bold">
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    ثبت مرخصی جدید
                </a>
                <a href="{{ route('specialist.profile.show') }}"
                   class="flex items-center px-4 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition"
                   style="border: 1px solid var(--specialist-border);">
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="specialist-card overflow-hidden">
            <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">لیست مرخصی‌ها</h2>
                <p class="text-sm text-[var(--specialist-text-dim)] mt-1">مشاهده درخواست‌های مرخصی</p>
            </div>

            @forelse($leaves as $leave)
                @php
                    $leaveInfo = $leaveStatusMap[$leave->status] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                @endphp
                <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-start gap-3 flex-1">
                            <span class="rounded-full w-9 h-9 flex items-center justify-center flex-shrink-0" style="background-color: rgba(216, 174, 224, 0.12); color: var(--specialist-plum-mid);">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm text-[var(--specialist-text)] persian-number">
                                    <span class="text-[var(--specialist-plum-muted)]">از</span> {{ jdate($leave->start_date)->format('Y/m/d') }}
                                    <span class="text-[var(--specialist-plum-muted)]">تا</span> {{ jdate($leave->end_date)->format('Y/m/d') }}
                                </p>
                                <p class="text-sm text-[var(--specialist-text-dim)] mt-1">
                                    @if($leave->reason)
                                        {{ $leave->reason }}
                                    @else
                                        <span class="text-[var(--specialist-inactive)]">بدون توضیحات</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $leaveInfo['class'] }}">{{ $leaveInfo['label'] }}</span>

                            @if($leave->status === 'pending')
                                <button type="button"
                                        class="text-red-300 hover:text-red-200 transition-colors text-sm flex items-center gap-1"
                                        onclick="showDeleteLeaveModal('{{ route('specialist.leaves.destroy', $leave) }}')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    حذف
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-[var(--specialist-inactive)]">
                    <svg class="w-14 h-14 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p class="mb-2 font-medium text-[var(--specialist-text-dim)]">هیچ درخواست مرخصی ثبت نشده است</p>
                    <a href="{{ route('specialist.leaves.create') }}" class="text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] text-sm font-medium">
                        اولین مرخصی خود را ثبت کنید
                    </a>
                </div>
            @endforelse

            @if($leaves->hasPages())
                <div class="p-4 border-t" style="border-color: var(--specialist-border);">
                    {{ $leaves->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div id="deleteLeaveModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="specialist-card p-6 max-w-sm w-full border" style="border-color: var(--specialist-border);">
            <h3 class="text-lg font-bold mb-4 text-red-300 font-serif-fa">تایید حذف درخواست</h3>
            <p class="text-[var(--specialist-text-dim)] mb-6" id="deleteLeaveMessage">
                آیا از حذف این درخواست مرخصی اطمینان دارید؟ این عملیات قابل بازگشت نیست.
            </p>
            <form id="deleteLeaveForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-500 transition">بله، حذف شود</button>
                    <button type="button" onclick="hideDeleteLeaveModal()" class="flex-1 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 transition" style="border: 1px solid var(--specialist-border);">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showDeleteLeaveModal(url) {
            document.getElementById('deleteLeaveForm').action = url;
            document.getElementById('deleteLeaveModal').classList.remove('hidden');
        }

        function hideDeleteLeaveModal() {
            document.getElementById('deleteLeaveModal').classList.add('hidden');
        }

        document.getElementById('deleteLeaveModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteLeaveModal();
            }
        });
    </script>
@endsection
