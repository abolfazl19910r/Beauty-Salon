@extends('layouts.admin')
@section('title', $post->title)

@section('content')
    <div class="fade-in">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">نمایش مقاله</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.edit', $post) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
                   style="background:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-accent-hover)'"
                   onmouseout="this.style.background='var(--admin-accent)'">
                    ویرایش
                </a>
                <a href="{{ route('admin.blog.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @if($post->image_url)
                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-72 object-cover">
            @endif

            <div class="p-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $post->is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $post->is_published ? 'منتشر شده' : 'پیش‌نویس' }}
                    </span>
                    @if($post->category)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                            {{ $post->category->name }}
                        </span>
                    @endif
                </div>

                <h2 class="text-2xl font-bold mb-3" style="color:var(--admin-text);">{{ $post->title }}</h2>

                <div class="flex items-center gap-4 text-xs mb-6" style="color:var(--admin-text-dim);">
                    <span class="persian-number">تاریخ انتشار: {{ $post->published_at_jalali ?? 'تنظیم نشده' }}</span>
                    <span class="persian-number">بازدید: {{ number_format($post->views ?? 0) }}</span>
                    @if($post->author)
                        <span>نویسنده: {{ $post->author->name }}</span>
                    @endif
                </div>

                @if($post->excerpt)
                    <p class="text-sm mb-5 pb-5" style="color:var(--admin-text-dim); border-bottom:1px solid var(--admin-border);">
                        {{ $post->excerpt }}
                    </p>
                @endif

                <div class="prose max-w-none text-sm leading-relaxed" style="color:var(--admin-text);">
                    {!! $post->content !!}
                </div>
            </div>
        </div>
    </div>
@endsection
