@extends('layouts.app')

@section('title', 'نشست‌های فعال')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('security.dashboard') }}"
               class="w-9 h-9 rounded-xl bg-m-card border border-m-gold/15 flex items-center justify-center
                  text-m-tx/60 hover:text-m-gold2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-0.5">امنیت حساب</p>
                <h1 class="text-2xl font-bold text-m-gold2"
                    style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">نشست‌های فعال</h1>
            </div>
        </div>

        @if ($sessions->count() > 1)
            <div class="flex justify-end mb-4">
                <button type="button" id="terminate-all-btn"
                        class="px-5 py-2 rounded-xl text-sm font-semibold border border-m-bad/25 text-m-bad hover:bg-m-bad/10 transition-colors">
                    پایان دادن به تمام نشست‌های دیگر
                </button>
            </div>
        @endif

        <div id="sessions-list" class="space-y-3">
            @foreach ($sessions as $session)
                <div class="bg-m-card rounded-2xl border border-m-gold/10 p-5 flex items-center justify-between"
                     data-session-id="{{ $session->id }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-m-gold/70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <div>
                            <p class="text-sm text-m-tx/85">
                                {{ $session->ip_address ?: 'IP نامشخص' }}
                                @if ($session->is_current_device)
                                    <span class="text-xs text-m-ok mr-1">(همین دستگاه)</span>
                                @endif
                            </p>
                            <p class="text-xs text-m-tx/40 mt-0.5">
                                آخرین فعالیت: {{ jalali_date($session->last_activity, 'Y/m/d H:i') }}
                            </p>
                        </div>
                    </div>
                    @unless ($session->is_current_device)
                        <button type="button" class="terminate-session-btn text-xs text-m-bad hover:underline"
                                data-session-id="{{ $session->id }}">
                            پایان نشست
                        </button>
                    @endunless
                </div>
            @endforeach
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        async function terminateSession(id, btn) {
            if (!confirm('نشست این دستگاه پایان یابد؟')) return;
            btn.disabled = true;
            try {
                const res = await fetch(`{{ url('/security/sessions') }}/${id}/terminate`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok) { alert(data.error || 'خطایی رخ داد.'); btn.disabled = false; return; }
                document.querySelector(`[data-session-id="${id}"]`).remove();
            } catch (e) {
                alert('خطا در ارتباط با سرور.');
                btn.disabled = false;
            }
        }

        document.querySelectorAll('.terminate-session-btn').forEach(btn => {
            btn.addEventListener('click', () => terminateSession(btn.dataset.sessionId, btn));
        });

        const terminateAllBtn = document.getElementById('terminate-all-btn');
        if (terminateAllBtn) {
            terminateAllBtn.addEventListener('click', async () => {
                if (!confirm('تمام نشست‌های دیگر پایان یابند؟ (این دستگاه دست‌نخورده می‌ماند)')) return;
                terminateAllBtn.disabled = true;
                try {
                    const res = await fetch('{{ route('security.sessions.terminate-all') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    });
                    if (!res.ok) { alert('خطایی رخ داد.'); terminateAllBtn.disabled = false; return; }
                    window.location.reload();
                } catch (e) {
                    alert('خطا در ارتباط با سرور.');
                    terminateAllBtn.disabled = false;
                }
            });
        }
    </script>
@endsection
