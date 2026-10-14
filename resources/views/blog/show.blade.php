@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <style>
        .prose-rasta { color: rgb(var(--m-tx) / 0.85); line-height: 2; }
        .prose-rasta p { margin-bottom: 1.25rem; }
        .prose-rasta img { border-radius: 1rem; margin: 1.5rem 0; }
        .prose-rasta h2, .prose-rasta h3 { color: rgb(var(--m-gold2)); font-weight: 700; margin: 1.5rem 0 0.75rem; }
        .prose-rasta a { color: rgb(var(--m-gold)); text-decoration: underline; }
    </style>

    <div class="max-w-3xl mx-auto fade-in">
        {{-- Breadcrumb --}}
        <div class="flex items-center flex-wrap gap-2 text-sm mb-6 text-m-tx/50">
            <a href="{{ route('home') }}" class="hover:text-m-gold2 transition-colors">خانه</a>
            <span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-m-gold2 transition-colors">وبلاگ</a>
            @if($post->category)
                <span>/</span>
                <a href="{{ route('blog.index', ['category' => $post->category_id]) }}" class="hover:text-m-gold2 transition-colors">
                    {{ $post->category->name }}
                </a>
            @endif
        </div>

        <article class="bg-m-card rounded-2xl overflow-hidden border border-m-gold/10">
            @if($post->image_url)
                <div class="h-72 md:h-96 overflow-hidden">
                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                </div>
            @endif

            <div class="p-6 md:p-10">
                @if($post->category)
                    <a href="{{ route('blog.index', ['category' => $post->category_id]) }}"
                       class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-4
                              bg-m-gold/10 text-m-gold2 border border-m-gold/30">
                        {{ $post->category->name }}
                    </a>
                @endif

                <h1 class="text-2xl md:text-4xl font-bold mb-4 leading-tight text-m-tx"
                    style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                    {{ $post->title }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-sm text-m-tx/50 mb-8 pb-6 border-b border-m-gold/10">
                    <span class="flex items-center gap-1.5 persian-number">
                        <svg class="w-4 h-4 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        {{ $post->published_at_jalali }}
                    </span>
                    <span class="flex items-center gap-1.5 persian-number">
                        <svg class="w-4 h-4 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        {{ number_format($post->views ?? 0) }} بازدید
                    </span>
                    @if($post->author)
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                            {{ $post->author->name }}
                        </span>
                    @endif
                </div>

                <div class="prose-rasta max-w-none">
                    {!! $post->content !!}
                </div>
            </div>
        </article>

        {{-- Related posts --}}
        @if($relatedPosts->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-xl font-bold text-m-gold2 mb-5" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                    مقالات مرتبط
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    @foreach($relatedPosts as $related)
                        <a href="{{ route('blog.show', $related) }}"
                           class="bg-m-card rounded-xl overflow-hidden border border-m-gold/10 block group">
                            <div class="h-32 overflow-hidden">
                                @if($related->image_url)
                                    <img src="{{ $related->image_url }}" alt="{{ $related->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-m-bg"></div>
                                @endif
                            </div>
                            <div class="p-4">
                                <p class="text-sm font-semibold text-m-tx line-clamp-2">{{ $related->title }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10 text-center">
            <a href="{{ route('blog.index') }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold
                      bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                      hover:shadow-lg hover:shadow-[#C9A24B]/25 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
                </svg>
                بازگشت به لیست مقالات
            </a>
        </div>
    </div>
@endsection
