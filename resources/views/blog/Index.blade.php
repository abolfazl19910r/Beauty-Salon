@extends('layouts.app')

@section('title', 'وبلاگ')

@section('content')
    <style>
        .blog-card-hover { transition: transform 0.35s ease, box-shadow 0.35s ease; }
        .blog-card-hover:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.4); }
        .blog-card-hover .img-zoom { transition: transform 0.55s ease; }
        .blog-card-hover:hover .img-zoom { transform: scale(1.07); }

        .category-pill { transition: all 0.25s ease; }
        .category-pill.active {
            background: linear-gradient(135deg, #E6CD8A, #C9A24B);
            color: #1A1410;
            font-weight: 600;
        }
        .category-pill:not(.active) {
            background: rgba(201,162,75,0.1);
            color: rgba(248,243,233,0.75);
            border: 1px solid rgba(201,162,75,0.2);
        }
        .category-pill:not(.active):hover {
            background: rgba(201,162,75,0.2);
            color: #E6CD8A;
        }

        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] a {
            background: rgba(201,162,75,0.1) !important;
            border-color: rgba(201,162,75,0.2) !important;
            color: #E6CD8A !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background: linear-gradient(135deg, #E6CD8A, #C9A24B) !important;
            color: #1A1410 !important;
            font-weight: 700 !important;
        }
    </style>

    {{-- Page header --}}
    <div class="mb-10 fade-in">
        <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-2">وبلاگ سالن راستا</p>
        <h1 class="text-3xl md:text-4xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
            مقالات و نکات زیبایی
        </h1>
        <p class="text-[#F8F3E9]/60 mt-2">آخرین مطالب، آموزش‌ها و اخبار سالن راستا</p>
    </div>

    {{-- Category filter --}}
    @if($categories->isNotEmpty())
        <div class="mb-8 fade-in">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                <span class="text-sm font-medium text-[#F8F3E9]/70">دسته‌بندی مقالات</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('blog.index') }}"
                   class="category-pill px-4 py-2 rounded-full text-sm {{ !request('category') ? 'active' : '' }}">
                    همه مقالات
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('blog.index', ['category' => $category->id]) }}"
                       class="category-pill px-4 py-2 rounded-full text-sm {{ request('category') == $category->id ? 'active' : '' }}">
                        {{ $category->name }}
                        <span class="persian-number">({{ $category->posts_count }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Post grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($posts as $post)
            <a href="{{ route('blog.show', $post) }}"
               class="blog-card-hover bg-[#2E2117] rounded-2xl overflow-hidden border border-[#C9A24B]/10 flex flex-col fade-in">
                <div class="overflow-hidden h-48 relative">
                    @if($post->image_url)
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}"
                             loading="lazy" class="img-zoom w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-[#1A1410]">
                            <svg class="w-10 h-10 text-[#C9A24B]/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                            </svg>
                        </div>
                    @endif
                    @if($post->category)
                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full
                                 bg-[#1A1410]/80 text-[#E6CD8A] border border-[#C9A24B]/30 backdrop-blur-sm">
                            {{ $post->category->name }}
                        </span>
                    @endif
                </div>

                <div class="p-5 flex flex-col flex-grow">
                    <h3 class="text-lg font-bold text-[#F8F3E9] mb-2 line-clamp-2"
                        style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
                        {{ $post->title }}
                    </h3>
                    <p class="text-sm text-[#F8F3E9]/60 leading-7 line-clamp-2 mb-4 flex-grow">
                        {{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 100) }}
                    </p>

                    <div class="flex items-center justify-between text-xs border-t border-[#C9A24B]/10 pt-4">
                        <span class="flex items-center gap-1 text-[#F8F3E9]/50 persian-number">
                            <svg class="w-3.5 h-3.5 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            {{ $post->published_at_jalali }}
                        </span>
                        <span class="flex items-center gap-1 text-[#F8F3E9]/50 persian-number">
                            <svg class="w-3.5 h-3.5 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            {{ number_format($post->views ?? 0) }}
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-20 fade-in">
                <div class="w-20 h-20 rounded-full bg-[#C9A24B]/10 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-[#C9A24B]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <p class="text-[#F8F3E9]/50 text-lg">هیچ مقاله‌ای یافت نشد.</p>
                @if(request('category'))
                    <a href="{{ route('blog.index') }}" class="mt-4 inline-block text-sm text-[#E6CD8A] hover:underline">
                        مشاهده همه مقالات
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
        <div class="mt-10 flex justify-center fade-in">
            {{ $posts->links() }}
        </div>
    @endif
@endsection
