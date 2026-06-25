@extends('layouts.admin')
@section('title', 'مدیریت خدمات')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    مدیریت خدمات
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">مدیریت خدمات قابل ارائه سالن</p>
            </div>
            @permission('create-services')
            <a href="{{ route('admin.services.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
               style="background:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-accent-hover)'"
               onmouseout="this.style.background='var(--admin-accent)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن خدمت جدید
            </a>
            @endpermission
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تصویر</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">نام خدمت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">دسته‌بندی</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">قیمت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">مدت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($services as $service)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3">
                                @if($service->image)
                                    <img src="{{ $service->image_url }}" alt="{{ $service->name }}"
                                         class="w-12 h-12 object-cover rounded-lg">
                                @else
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                                         style="background:var(--admin-accent-light); color:var(--admin-text-light);">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium" style="color:var(--admin-text);">{{ $service->name }}</td>
                            <td class="px-4 py-3">
                                @if($service->category)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                    {{ $service->category->name }}
                                </span>
                                @else
                                    <span style="color:var(--admin-text-light);">بدون دسته‌بندی</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number font-medium" style="color:#16A34A;">
                                {{ number_format($service->price) }}
                                <span class="text-xs font-normal" style="color:var(--admin-text-light);">تومان</span>
                            </td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                                {{ $service->duration }} دقیقه
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    @permission('edit-services')
                                    <a href="{{ route('admin.services.edit', $service) }}" title="ویرایش"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:#7C3AED;"
                                       onmouseover="this.style.background='#F5F3FF'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    @endpermission
                                    @permission('delete-services')
                                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" title="حذف"
                                                data-confirm-delete data-confirm-message="آیا از حذف خدمت «{{ $service->name }}» اطمینان دارید؟"
                                                class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                style="color:#DC2626;"
                                                onmouseover="this.style.background='#FEF2F2'"
                                                onmouseout="this.style.background=''">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endpermission
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">
                                هیچ خدمتی ثبت نشده
                                @permission('create-services')
                                <br><a href="{{ route('admin.services.create') }}" class="inline-block mt-3 text-xs px-3 py-1.5 rounded-lg text-white" style="background:var(--admin-accent);">افزودن خدمت جدید</a>
                                @endpermission
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($services->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $services->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
