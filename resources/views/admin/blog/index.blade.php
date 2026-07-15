@extends('layouts.admin')
@section('title', 'مدیریت وبلاگ')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    </svg>
                    مدیریت وبلاگ
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">مدیریت مقالات وبلاگ سالن</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.categories.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    دسته‌بندی‌ها
                </a>
                <a href="{{ route('admin.blog.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
                   style="background:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-accent-hover)'"
                   onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    مقاله جدید
                </a>
            </div>
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            @php
                $statCards = [
                    ['label' => 'تعداد مقالات', 'value' => number_format($stats['post_count'])],
                    ['label' => 'دسته‌بندی‌ها', 'value' => number_format($stats['category_count'])],
                    ['label' => 'مجموع بازدیدها', 'value' => number_format($stats['total_views'])],
                ];
            @endphp
            @foreach($statCards as $card)
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <p class="text-xs mb-1" style="color:var(--admin-text-dim);">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold" style="color:var(--admin-text);">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @if($posts->isEmpty())
                <div class="p-10 text-center text-sm" style="color:var(--admin-text-dim);">
                    هیچ مقاله‌ای یافت نشد
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead>
                        <tr style="border-bottom:1px solid var(--admin-border)">
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">عنوان</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">دسته‌بندی</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">تاریخ انتشار</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">بازدید</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($posts as $post)
                            <tr style="border-bottom:1px solid var(--admin-border)">
                                <td class="p-4" style="color:var(--admin-text);">
                                    <div class="flex items-center gap-3">
                                        @if($post->image_url)
                                            <img src="{{ $post->image_url }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" alt="">
                                        @endif
                                        <span class="font-medium">{{ \Illuminate\Support\Str::limit($post->title, 40) }}</span>
                                    </div>
                                </td>
                                <td class="p-4" style="color:var(--admin-text-dim);">{{ $post->category->name ?? '—' }}</td>
                                <td class="p-4 persian-number" style="color:var(--admin-text-dim);">{{ $post->published_at_jalali ?? '—' }}</td>
                                <td class="p-4 persian-number" style="color:var(--admin-text-dim);">{{ number_format($post->views ?? 0) }}</td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $post->is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $post->is_published ? 'منتشر شده' : 'پیش‌نویس' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.blog.show', $post) }}" class="text-xs font-medium" style="color:var(--admin-text-dim);">نمایش</a>
                                        <a href="{{ route('admin.blog.edit', $post) }}" class="text-xs font-medium" style="color:var(--admin-accent);">ویرایش</a>
                                        <form action="{{ route('admin.blog.toggle-publish', $post) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-medium" style="color:#2563eb;">
                                                {{ $post->is_published ? 'پیش‌نویس کردن' : 'انتشار' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.blog.destroy', $post) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-confirm-delete class="text-xs font-medium" style="color:#dc2626;">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
