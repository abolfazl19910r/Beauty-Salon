@extends('layouts.app')

@section('title', 'خدمات')

@section('content')
    <style>
        .card-hover { transition: transform 0.35s ease, box-shadow 0.35s ease; }
        .card-hover:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.4); }
        .card-hover .img-zoom { transition: transform 0.55s ease; }
        .card-hover:hover .img-zoom { transform: scale(1.07); }

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

        /* override pagination links */
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

    {{-- هدر صفحه --}}
    <div class="mb-10 fade-in">
        <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-2">خدمات سالن راستا</p>
        <h1 class="text-3xl md:text-4xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
            خدمات ویژه ما
        </h1>
        <p class="text-[#F8F3E9]/60 mt-2">بهترین خدمات زیبایی با متخصص‌ترین تیم</p>
    </div>

    {{-- فیلتر دسته‌بندی --}}
    <div class="mb-8 fade-in">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            <span class="text-sm font-medium text-[#F8F3E9]/70">دسته‌بندی خدمات</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('services.index') }}"
               class="category-pill px-4 py-2 rounded-full text-sm {{ !request('category') ? 'active' : '' }}">
                همه خدمات
            </a>
            @foreach($categories as $category)
                <a href="{{ route('services.index', ['category' => $category->id]) }}"
                   class="category-pill px-4 py-2 rounded-full text-sm {{ request('category') == $category->id ? 'active' : '' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- گرید خدمات --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $index => $service)
            <div class="card-hover bg-[#2E2117] rounded-2xl overflow-hidden border border-[#C9A24B]/10 flex flex-col fade-in">
                {{-- تصویر --}}
                <div class="overflow-hidden h-52 relative">
                    @if($service->image)
                        <img src="{{ $service->image_url }}" alt="{{ $service->name }}"
                             loading="lazy" class="img-zoom w-full h-full object-cover">
                    @else
                        <img src="{{ asset('images/placeholder-service.svg') }}" alt="{{ $service->name }}"
                             class="w-full h-full object-cover">
                    @endif
                    {{-- برچسب دسته --}}
                    @if($service->category)
                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full
                                 bg-[#1A1410]/80 text-[#E6CD8A] border border-[#C9A24B]/30 backdrop-blur-sm">
                        {{ $service->category->name }}
                    </span>
                    @endif
                </div>

                {{-- محتوا --}}
                <div class="p-5 flex flex-col flex-grow">
                    <h3 class="text-lg font-bold text-[#F8F3E9] mb-2"
                        style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
                        {{ $service->name }}
                    </h3>
                    <p class="text-sm text-[#F8F3E9]/60 leading-7 line-clamp-2 mb-4 flex-grow">
                        {{ $service->description }}
                    </p>

                    {{-- مدت‌زمان و قیمت --}}
                    <div class="flex items-center justify-between text-sm border-t border-[#C9A24B]/10 pt-4 mb-4">
                    <span class="flex items-center gap-1 text-[#F8F3E9]/60">
                        <svg class="w-4 h-4 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                        </svg>
                        <span class="persian-number">{{ $service->duration }} دقیقه</span>
                    </span>
                        <span class="text-[#E6CD8A] font-bold persian-number">
                        {{ number_format($service->price) }} تومان
                    </span>
                    </div>

                    {{-- دکمه‌ها --}}
                    <div class="flex gap-2">
                        <a href="{{ route('services.show', $service) }}"
                           class="flex-1 text-center py-2.5 rounded-xl text-sm border border-[#C9A24B]/30
                              text-[#E6CD8A] hover:bg-[#C9A24B]/10 transition-colors">
                            جزئیات
                        </a>
                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                           class="flex-1 text-center py-2.5 rounded-xl text-sm font-semibold
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                              hover:shadow-lg hover:shadow-[#C9A24B]/25 transition-all">
                            رزرو نوبت
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 fade-in">
                <div class="w-20 h-20 rounded-full bg-[#C9A24B]/10 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-[#C9A24B]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <p class="text-[#F8F3E9]/50 text-lg">هیچ خدمتی در این دسته یافت نشد.</p>
                <a href="{{ route('services.index') }}" class="mt-4 inline-block text-sm text-[#E6CD8A] hover:underline">
                    مشاهده همه خدمات
                </a>
            </div>
        @endforelse
    </div>

    {{-- صفحه‌بندی --}}
    @if($services->hasPages())
        <div class="mt-10 flex justify-center fade-in">
            {{ $services->links() }}
        </div>
    @endif

@endsection
