@extends('layouts.app')

@section('title', 'خدمات')

@section('content')
    <style>
        .card-hover { transition: transform 0.35s ease, box-shadow 0.35s ease; }
        .card-hover:hover { transform: translateY(-6px); box-shadow: var(--m-sh-soft); }
        .card-hover .img-zoom { transition: transform 0.55s ease; }
        .card-hover:hover .img-zoom { transform: scale(1.07); }

        .category-pill { transition: all 0.25s ease; }
        .m-cat-row { scrollbar-width: thin; scroll-snap-type: x proximity; }
        .m-cat-row > a { scroll-snap-align: start; }
        .category-pill.active {
            background: var(--m-btn);
            color: var(--m-on-gold);
            font-weight: 600;
        }
        .category-pill:not(.active) {
            background: rgb(var(--m-gold) / 0.1);
            color: rgb(var(--m-tx) / 0.75);
            border: 1px solid rgb(var(--m-gold) / 0.2);
        }
        .category-pill:not(.active):hover {
            background: rgb(var(--m-gold) / 0.2);
            color: rgb(var(--m-gold2));
        }

        /* override pagination links */
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] a {
            background: rgb(var(--m-gold) / 0.1) !important;
            border-color: rgb(var(--m-gold) / 0.2) !important;
            color: rgb(var(--m-gold2)) !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background: var(--m-btn) !important;
            color: var(--m-on-gold) !important;
            font-weight: 700 !important;
        }
    </style>

    {{-- Page header --}}
    <div class="mb-10 fade-in">
        <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-2">خدمات {{ $currentSalonTitle }}</p>
        <h1 class="text-3xl md:text-4xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
            خدمات ویژه ما
        </h1>
        <p class="text-m-tx/60 mt-2">{{ $currentSalonTagline }}</p>
    </div>

    {{-- Category filter --}}
    <div class="mb-8 fade-in">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            <span class="text-sm font-medium text-m-tx/70">دسته‌بندی خدمات</span>
        </div>
        {{-- بسته‌ی «ح»، ۱۱: یک ردیف کشیدنی به‌جای دیوار چندخطی دکمه‌ها؛ فهرست کامل در منوی «خدمات» هدر --}}
        <div class="m-cat-row flex flex-nowrap gap-2 overflow-x-auto pb-2 -mx-1 px-1" data-category-row>
            <a href="{{ route('services.index') }}"
               class="category-pill shrink-0 whitespace-nowrap px-4 py-2 rounded-full text-sm {{ !request('category') ? 'active' : '' }}">
                همه خدمات
            </a>
            @foreach(\App\Support\SalonNavCategories::get() as $category)
                <a href="{{ route('services.index', ['category' => $category->id]) }}"
                   class="category-pill shrink-0 whitespace-nowrap px-4 py-2 rounded-full text-sm {{ request('category') == $category->id ? 'active' : '' }}"
                   @if (request('category') == $category->id) aria-current="true" @endif>
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Service Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $index => $service)
            <div class="card-hover bg-m-card rounded-2xl overflow-hidden border border-m-gold/10 flex flex-col fade-in">
                {{-- Image --}}
                <div class="overflow-hidden h-52 relative">
                    @if($service->image)
                        <img src="{{ $service->image_url }}" alt="{{ $service->name }}"
                             loading="lazy" class="img-zoom w-full h-full object-cover">
                    @else
                        <img src="{{ asset('images/placeholder-service.svg') }}" alt="{{ $service->name }}"
                             class="w-full h-full object-cover">
                    @endif
                    {{-- Category label --}}
                    @if($service->category)
                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full
                                 bg-m-bg/80 text-m-gold2 border border-m-gold/30 backdrop-blur-sm">
                        {{ $service->category->name }}
                    </span>
                    @endif
                </div>

                {{-- Content --}}
                <div class="p-5 flex flex-col flex-grow">
                    <h3 class="text-lg font-bold text-m-tx mb-2"
                        style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                        {{ $service->name }}
                    </h3>
                    <p class="text-sm text-m-tx/60 leading-7 line-clamp-2 mb-4 flex-grow">
                        {{ $service->description }}
                    </p>

                    {{-- Duration and price --}}
                    <div class="flex items-center justify-between text-sm border-t border-m-gold/10 pt-4 mb-4">
                    <span class="flex items-center gap-1 text-m-tx/60">
                        <svg class="w-4 h-4 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                        </svg>
                        <span class="persian-number">{{ $service->duration }} دقیقه</span>
                    </span>
                        <span class="text-m-gold2 font-bold persian-number">
                        {{ number_format($service->price) }} تومان
                    </span>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-2">
                        <a href="{{ route('services.show', $service) }}"
                           class="flex-1 text-center py-2.5 rounded-xl text-sm border border-m-gold/30
                              text-m-gold2 hover:bg-m-gold/10 transition-colors">
                            جزئیات
                        </a>
                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                           class="flex-1 text-center py-2.5 rounded-xl text-sm font-semibold
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                              hover:shadow-lg hover:shadow-[#C9A24B]/25 transition-all">
                            رزرو نوبت
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 fade-in">
                <div class="w-20 h-20 rounded-full bg-m-gold/10 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-m-gold/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <p class="text-m-tx/50 text-lg">هیچ خدمتی در این دسته یافت نشد.</p>
                <a href="{{ route('services.index') }}" class="mt-4 inline-block text-sm text-m-gold2 hover:underline">
                    مشاهده همه خدمات
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($services->hasPages())
        <div class="mt-10 flex justify-center fade-in">
            {{ $services->links() }}
        </div>
    @endif

@endsection

@push('scripts')
<script>
    // دسته‌ی انتخاب‌شده در ردیف کشیدنی دیده شود
    document.querySelector('[data-category-row] .category-pill.active')?.scrollIntoView({ block: 'nearest', inline: 'center' });
</script>
@endpush
