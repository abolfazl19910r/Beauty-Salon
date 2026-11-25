@extends('layouts.app')

{{--
    ⭐ بسته‌ی ۵الف — صفحه‌ی عمومی «نظرها»ی سالن (درخواست ابوالفضل، ۲۰۲۶-۱۱-۲۵؛ تصمیم‌ها: نام کوچک + حرف اول، پیوند بیرون از
    منوی اصلی، ایندکس و نقشه‌ی سایت، فیلتر کارشناس و ستاره). فقط‌خواندنی: ثبت نظر فقط از پیوند پیامکی پس از انجام خدمت.
    رنگ‌ها فقط توکن‌های --m-* تا در طلایی ماهرو و هر ۴ پالت درست باشد. داده: SalonReviewController.
--}}

@section('title', 'نظر مشتری‌ها | '.$currentSalonName)

@section('content')
    @php
        $rvStar = fn (int $n, string $size = 'w-4 h-4') => collect(range(1, 5))->map(fn ($i) => '<svg class="'.$size.' '.($i <= $n ? 'text-m-gold' : 'text-m-mu/30').'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8z"/></svg>')->implode('');
        $rvMax = max(1, max($stats['distribution']));
        $rvFiltered = $filters['specialist'] || $filters['rating'];
    @endphp
    <div class="max-w-5xl mx-auto py-6" data-salon-reviews>
        <div class="mb-6">
            <h1 class="font-serif-fa text-3xl md:text-4xl font-bold text-m-tx">نظر مشتری‌ها</h1>
            <p class="mt-2 text-sm text-m-mu">نظر مشتری‌های {{ $currentSalonName }} درباره‌ی خدمت و کارشناس‌ها، همراه با پاسخ کارشناس.</p>
        </div>

        @if ($stats['total'] > 0)
            <section class="m-card bg-m-bg2 border border-m-gold/15 rounded-2xl p-5 md:p-6 grid gap-6 md:grid-cols-[220px_1fr] items-center" aria-label="خلاصه‌ی امتیاز">
                <div class="text-center md:border-l md:border-m-gold/15 md:pl-6">
                    <div class="text-5xl font-bold text-m-gold persian-number">{{ fa_num($stats['average'], 1) }}</div>
                    <div class="flex justify-center gap-0.5 mt-2" aria-label="{{ fa_num($stats['average'], 1) }} از ۵">{!! $rvStar((int) round($stats['average']), 'w-5 h-5') !!}</div>
                    <div class="mt-1.5 text-sm text-m-mu">از {{ fa_num($stats['total']) }} نظر</div>
                </div>
                <div class="flex flex-col gap-2" data-rating-bars>
                    @foreach ($stats['distribution'] as $star => $count)
                        <a href="{{ route('salon.reviews', array_filter(['rating' => $star, 'specialist' => $filters['specialist']])) }}"
                           class="grid grid-cols-[52px_1fr_44px] items-center gap-3 text-sm group {{ $filters['rating'] === $star ? 'font-bold' : '' }}">
                            <span class="text-m-tx persian-number">{{ fa_num($star) }} ستاره</span>
                            <span class="h-2.5 rounded-full bg-m-bg3 overflow-hidden"><span class="block h-full rounded-full bg-m-gold group-hover:bg-m-gold2" style="width: {{ round($count / $rvMax * 100) }}%"></span></span>
                            <span class="text-m-mu text-left persian-number">{{ fa_num($count) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <form method="GET" action="{{ route('salon.reviews') }}" class="mt-5 flex flex-wrap items-center gap-2 text-sm" data-reviews-filter>
                @if ($experts->count() > 1)
                    <label class="sr-only" for="rv-specialist">کارشناس</label>
                    <select id="rv-specialist" name="specialist" data-ui-autosubmit class="bg-m-bg2 border border-m-gold/20 rounded-xl px-3 py-2 text-m-tx">
                        <option value="">همه‌ی کارشناس‌ها</option>
                        @foreach ($experts as $expert)
                            <option value="{{ $expert->id }}" @selected($filters['specialist'] === (int) $expert->id)>{{ $expert->name }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($filters['rating'])
                    <input type="hidden" name="rating" value="{{ $filters['rating'] }}">
                @endif
                @foreach ([null, 5, 4, 3, 2, 1] as $star)
                    <a href="{{ route('salon.reviews', array_filter(['rating' => $star, 'specialist' => $filters['specialist']])) }}"
                       class="px-3 py-1.5 rounded-full border persian-number {{ $filters['rating'] === $star ? 'bg-m-gold text-m-ongold border-transparent' : 'border-m-gold/20 text-m-tx hover:border-m-gold/50' }}"
                       @if ($filters['rating'] === $star) aria-current="true" @endif>{{ $star ? fa_num($star).' ستاره' : 'همه' }}</a>
                @endforeach
                <noscript><button type="submit" class="px-3 py-1.5 rounded-full border border-m-gold/30">اعمال</button></noscript>
            </form>
        @endif

        <p class="mt-4 text-xs text-m-mu leading-6">هر مشتری بعد از انجام خدمت، نظرش را از پیوندِ پیامکِ همان نوبت ثبت می‌کند؛ کارشناس می‌تواند پاسخ بدهد.</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @forelse ($reviews as $review)
                <article class="m-card bg-m-card border border-m-gold/10 rounded-2xl p-5 flex flex-col gap-2.5" data-review-card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <b class="text-m-tx">{{ \App\Support\Customer\CustomerName::short($review->user?->name) }}</b>
                            <div class="text-xs text-m-mu mt-0.5 persian-number">{{ fa_jdate($review->reviewed_at ?? $review->created_at, 'j F Y') }}</div>
                        </div>
                        <div class="flex gap-0.5 shrink-0" aria-label="{{ fa_num($review->overall_rating) }} از ۵">{!! $rvStar((int) $review->overall_rating) !!}</div>
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-m-mu">
                        @if ($review->specialist)
                            <a href="{{ route('specialists.show', $review->specialist) }}" class="text-m-gold2 hover:underline">{{ $review->specialist->name }}</a>
                        @endif
                        @if ($review->service)
                            <span>{{ $review->service->name }}</span>
                        @endif
                    </div>
                    @if ($review->comment)
                        <p class="text-sm leading-7 text-m-tx/90 whitespace-pre-line">{{ $review->comment }}</p>
                    @endif
                    @if ($review->specialist_response)
                        <div class="mt-1 rounded-xl bg-m-bg3/70 p-3 text-sm leading-7" data-review-reply>
                            <span class="block text-xs font-bold text-m-gold2 mb-0.5">پاسخ {{ $review->specialist?->name ?? 'کارشناس' }}</span>
                            <span class="text-m-tx/85 whitespace-pre-line">{{ $review->specialist_response }}</span>
                        </div>
                    @endif
                </article>
            @empty
                <div class="md:col-span-2 text-center py-14 text-m-mu" data-reviews-empty>
                    @if ($rvFiltered)
                        نظری با این فیلتر نیست. <a href="{{ route('salon.reviews') }}" class="text-m-gold2 underline">همه‌ی نظرها</a>
                    @else
                        هنوز نظری برای این سالن ثبت نشده است.
                    @endif
                </div>
            @endforelse
        </div>

        @if ($reviews->hasPages())
            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
