@extends('layouts.admin')
@section('title', 'دسته‌بندی: ' . $category->name)

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $category->name }}</h1>
            <div class="flex gap-2">
                @permission('edit-categories')
                <a href="{{ route('admin.categories.edit', $category) }}"
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
                <a href="{{ route('admin.categories.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        @if($category->description)
            <div class="rounded-xl p-4 mb-5 text-sm" style="background:var(--admin-accent-light); border:1px solid var(--admin-border); color:var(--admin-text-dim);">
                {{ $category->description }}
            </div>
        @endif

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                خدمات این دسته‌بندی ({{ $category->services->count() }})
            </div>
            @if($category->services->isEmpty())
                <p class="px-4 py-8 text-center text-sm" style="color:var(--admin-text-dim);">هیچ خدمتی در این دسته‌بندی وجود ندارد</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">نام خدمت</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">قیمت</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">مدت</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($category->services as $service)
                            <tr style="border-top:1px solid var(--admin-border);"
                                onmouseover="this.style.background='var(--admin-accent-light)'"
                                onmouseout="this.style.background=''">
                                <td class="px-4 py-3 font-medium" style="color:var(--admin-text);">{{ $service->name }}</td>
                                <td class="px-4 py-3 persian-number" style="color:#16A34A;">{{ number_format($service->price) }} تومان</td>
                                <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ $service->duration }} دقیقه</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.services.edit', $service) }}"
                                       class="text-xs px-2.5 py-1 rounded-lg"
                                       style="color:var(--admin-accent); background:var(--admin-accent-light);">ویرایش</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
