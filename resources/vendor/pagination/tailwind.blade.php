@if ($paginator->hasPages())
    @php
        $isSpecialist = request()->is('specialist*') || request()->is('my-dashboard*');
        $isAdmin      = request()->is('admin*');

        if ($isSpecialist) {
            $activeBg   = '#D8AEE0';
            $activeText = '#250D2B';
            $inactiveBg = 'transparent';
            $inactiveText = '#C9A6D1';
            $borderColor  = '#3A2640';
            $hoverBg      = 'rgba(216,174,224,0.12)';
            $wrapper      = 'display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;';
        } elseif ($isAdmin) {
            $activeBg   = '#334155';
            $activeText = '#FFFFFF';
            $inactiveBg = '#FFFFFF';
            $inactiveText = '#64748B';
            $borderColor  = '#E2E8F0';
            $hoverBg      = '#F1F5F9';
            $wrapper      = 'display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;';
        } else {
            $activeBg   = '#C9A24B';
            $activeText = '#1A1410';
            $inactiveBg = 'transparent';
            $inactiveText = 'rgba(248,243,233,0.65)';
            $borderColor  = 'rgba(201,162,75,0.25)';
            $hoverBg      = 'rgba(201,162,75,0.1)';
            $wrapper      = 'display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;';
        }

        $btnBase = 'display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:0.5rem;font-size:0.8125rem;font-family:inherit;border:1px solid ' . $borderColor . ';transition:background 0.15s ease;cursor:pointer;text-decoration:none;';
        $activeStyle   = $btnBase . 'background:' . $activeBg . ';color:' . $activeText . ';font-weight:600;border-color:' . $activeBg . ';';
        $inactiveStyle = $btnBase . 'background:' . $inactiveBg . ';color:' . $inactiveText . ';';
        $disabledStyle = $btnBase . 'background:' . $inactiveBg . ';color:' . $borderColor . ';cursor:not-allowed;opacity:0.5;';
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="{{ $wrapper }}">
        <div style="font-size:0.8125rem;color:{{ $inactiveText }};white-space:nowrap;" class="persian-number">
            {!! __('pagination.showing', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) !!}
        </div>

        <div style="display:flex;gap:4px;align-items:center;" class="persian-number" dir="ltr">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span style="{{ $disabledStyle }}" aria-disabled="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" style="{{ $inactiveStyle }}" rel="prev" aria-label="{{ __('pagination.previous') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span style="{{ $disabledStyle }}">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" style="{{ $activeStyle }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" style="{{ $inactiveStyle }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" style="{{ $inactiveStyle }}" rel="next" aria-label="{{ __('pagination.next') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </a>
            @else
                <span style="{{ $disabledStyle }}" aria-disabled="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
