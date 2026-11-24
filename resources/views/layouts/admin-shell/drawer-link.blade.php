<a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" @if (! empty($item['shortcut'])) data-shortcut="{{ $item['shortcut'] }}" @endif @if ($item['active']) aria-current="page" @endif
   class="adm-link flex items-center px-3 py-2.5 mb-0.5 text-sm font-medium rounded-lg transition-colors {{ $item['active'] ? 'sidebar-active' : '' }}">
    <svg class="w-5 h-5 ml-2 opacity-75 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
    <span class="flex-1">{{ $item['label'] }}</span>
    @if ($item['lock'])
        <span class="mr-auto text-xs" title="در پلن سالن شما نیست">🔒</span>
    @endif
    @if (($item['count'] ?? 0) > 0)
        <span class="adm-count" data-menu-count="{{ $item['key'] }}" aria-label="{{ fa_num($item['count']) }} مورد منتظر">{{ fa_num($item['count']) }}</span>
    @endif
    @if ($item['badge'])
        <span id="{{ $item['badge'] }}" data-notif-badge class="mr-2 px-2.5 py-0.5 rounded-full text-xs font-medium hidden"
              style="background: rgb(var(--m-bad) / .14); color: rgb(var(--m-bad));"></span>
    @endif
</a>
