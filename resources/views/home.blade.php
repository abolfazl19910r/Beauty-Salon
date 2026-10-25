@extends('layouts.app')

@section('title', $currentSalonName . ' | سالن زیبایی لوکس')

@section('full-width', true)

@section('content')

    <style>
        /* فونت، هدر شیشه‌ای و دکمه‌ها از partials/mahru-theme (سیستم طراحی ماهرو، ادامه ۷) */
        .font-sans-fa { font-family: "Mahru", "Vazirmatn", sans-serif; }

        /* Hero */
        .hero-bg {
            background-image: linear-gradient(180deg, rgb(var(--m-bg) / 0.55) 0%, rgb(var(--m-bg) / 0.75) 60%, rgb(var(--m-bg) / 0.95) 100%), url('https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=2000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        @media (max-width: 1024px) {
            .hero-bg { background-attachment: scroll; }
        }

        .eyebrow {
            letter-spacing: 0.35em;
        }

        /* Fade-up animation (Intersection Observer driven) */
        .fade-up {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .fade-up.in-view {
            opacity: 1;
            transform: translateY(0);
        }

        .fade-up-delay-1 { transition-delay: 0.1s; }
        .fade-up-delay-2 { transition-delay: 0.25s; }
        .fade-up-delay-3 { transition-delay: 0.4s; }

        /* Hero entrance */
        .hero-anim {
            opacity: 0;
            transform: translateY(30px);
            animation: heroIn 1s ease-out forwards;
        }
        .hero-anim.delay-1 { animation-delay: 0.2s; }
        .hero-anim.delay-2 { animation-delay: 0.45s; }
        .hero-anim.delay-3 { animation-delay: 0.7s; }

        @keyframes heroIn {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scroll indicator */
        .scroll-indicator {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(10px); }
        }

        /* Service / specialist card hover */
        .card-hover {
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: var(--m-sh-soft);
        }
        .card-hover img {
            transition: transform 0.6s ease;
        }
        .card-hover:hover img {
            transform: scale(1.08);
        }

        /* Gallery hover */
        .gallery-item img {
            transition: transform 0.5s ease, filter 0.5s ease;
        }
        .gallery-item:hover img {
            transform: scale(1.1);
            filter: brightness(0.7);
        }
        .gallery-item .overlay {
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .gallery-item:hover .overlay {
            opacity: 1;
        }

        /* Testimonial slider */
        .testimonial-slide {
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        /* CTA section */
        .cta-bg {
            background-image: linear-gradient(180deg, rgb(var(--m-bg) / 0.75), rgb(var(--m-bg) / 0.9)), url('https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?q=80&w=2000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }

        /* .btn-gold / .btn-glass از partials/mahru-theme (سیستم طراحی ماهرو) */
    </style>
    <div class="font-sans-fa bg-m-bg text-m-tx mx-[calc(50%-50vw)] w-screen -mt-8 -mb-8" dir="rtl">

        {{-- =========================================================
              (Hero)
        ========================================================= --}}
        <section class="hero-bg relative min-h-screen flex items-center justify-center text-center px-4">
            <div class="max-w-3xl mx-auto">
                <p class="hero-anim eyebrow text-sm md:text-base text-m-gold2 font-semibold mb-4 uppercase">
                    زیبایی &nbsp; اصیل &nbsp; ایرانی
                </p>
                <h1 class="hero-anim delay-1 font-serif-fa text-4xl md:text-6xl lg:text-7xl font-bold leading-tight mb-6 text-m-tx">
                    تجربه‌ای لوکس از زیبایی و آرامش در سالن <span class="text-m-gold2">{{ $currentSalonName }}</span>
                </h1>
                <p class="hero-anim delay-2 text-base md:text-lg text-m-tx/80 max-w-xl mx-auto mb-10 leading-8">
                    {{-- بسته‌ی مجوز 5د: شعار خود سالن؛ پیش‌تر وعده‌ی ثابت «میکاپ عروس… ناخن» برای هر سالنی --}}
                    {{ app(\App\Support\CurrentSalon::class)->get()?->tagline ?: 'نوبت خود را آنلاین رزرو کنید؛ خدمات، متخصص‌ها و ساعت‌های خالی را همین‌جا ببینید.' }}
                </p>
                <div class="hero-anim delay-3 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('bookings.create') }}" class="btn-gold rounded-full px-8 py-3.5 font-semibold w-full sm:w-auto">
                        رزرو نوبت آنلاین
                    </a>
                    <a href="#services" class="btn-glass text-m-tx rounded-full px-8 py-3.5 font-semibold w-full sm:w-auto">
                        مشاهده خدمات
                    </a>
                </div>
            </div>

            <a href="#stats" class="scroll-indicator absolute bottom-8 left-1/2 -translate-x-1/2 text-m-tx/70" aria-label="اسکرول به پایین">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </a>
        </section>

        {{-- =========================================================
             3) Statistics section / Trust counter
        ========================================================= --}}
        {{-- ⭐ بسته‌ی مجوز 5د: فقط عدد واقعی همین سالن؛ کاشی بی داده نمایش داده نمی‌شود (پیش‌تر ۴۵۰۰/۱۸/۴٫۹ ثابت) --}}
        @php
            $statTiles = array_values(array_filter([
                ($stats['customers'] ?? 0) > 0 ? ['value' => $stats['customers'], 'label' => 'مشتری', 'decimal' => 0] : null,
                ($stats['specialists'] ?? 0) > 0 ? ['value' => $stats['specialists'], 'label' => 'متخصص', 'decimal' => 0] : null,
                $currentSalonExperienceYears !== null ? ['value' => $currentSalonExperienceYears, 'label' => 'سال تجربه', 'decimal' => 0] : null,
                ($stats['reviews'] ?? 0) > 0 ? ['value' => $stats['rating'], 'label' => 'امتیاز ('.to_persian_num((string) $stats['reviews']).' نظر)', 'decimal' => 1] : null,
            ]));
        @endphp
        @if (count($statTiles) > 0)
        <section id="stats" class="bg-m-card py-14 fade-up">
            <div class="max-w-6xl mx-auto px-4 grid grid-cols-2 {{ count($statTiles) >= 4 ? 'md:grid-cols-4' : (count($statTiles) === 3 ? 'md:grid-cols-3' : '') }} gap-8 text-center">
                @foreach ($statTiles as $tile)
                    <div>
                        <p class="counter font-serif-fa text-3xl md:text-5xl font-bold text-m-gold2" data-target="{{ $tile['value'] }}" @if ($tile['decimal']) data-decimal="1" @endif>0</p>
                        <p class="mt-2 text-sm md:text-base text-m-tx/70">{{ $tile['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- =========================================================
             4) Special service department
        ========================================================= --}}
        <section id="services" class="py-20 px-4 max-w-7xl mx-auto">
            <div class="text-center mb-14 fade-up">
                <h2 class="font-serif-fa text-3xl md:text-5xl font-bold text-m-gold2 mb-4">خدمات ویژه</h2>
                <p class="text-m-tx/70 max-w-2xl mx-auto leading-8">
                    مجموعه‌ای از خدمات تخصصی زیبایی با بهترین مواد اولیه و تیمی مجرب، برای درخشش هر روز شما.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse(($services ?? []) as $index => $service)
                    <div class="fade-up fade-up-delay-{{ ($index % 3) + 1 }} card-hover bg-m-card rounded-2xl overflow-hidden border border-m-gold/10">
                        <div class="overflow-hidden h-56">
                            <img src="{{ $service->image_url ?? asset('images/placeholder-service.svg') }}" alt="{{ $service->name }}" loading="lazy" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-full bg-m-gold/15 flex items-center justify-center mb-4 text-m-gold2">

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                </svg>
                            </div>
                            <h3 class="font-serif-fa text-xl font-bold mb-2">{{ $service->name }}</h3>
                            <p class="text-sm text-m-tx/70 leading-7 mb-4">{{ $service->description ?? 'توضیح کوتاهی درباره این خدمت در اینجا نمایش داده می‌شود.' }}</p>
                            <div class="flex items-center justify-between text-sm border-t border-m-gold/10 pt-4">
                                <span class="text-m-gold2 font-bold persian-number">{{ number_format($service->price) }} تومان</span>
                                <span class="text-m-tx/60 persian-number">{{ $service->duration }} دقیقه</span>
                            </div>
                            <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="mt-4 block text-center btn-glass text-m-tx rounded-full py-2 text-sm font-semibold">
                                رزرو این خدمت
                            </a>
                        </div>
                    </div>
                @empty
                    {{-- بسته‌ی مجوز 5د: پیش‌تر سه خدمت ساختگی با عکس Unsplash --}}
                    <p class="col-span-full text-center text-m-tx/60" data-no-services>خدمات این سالن به‌زودی اضافه می‌شود.</p>
                @endforelse
            </div>
        </section>

        {{-- =========================================================
             5) About us section
        ========================================================= --}}
        <section class="py-20 px-4 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="fade-up order-2 lg:order-1">
                    <p class="eyebrow text-m-gold2 text-sm font-semibold mb-3">درباره &nbsp; ما</p>
                    <h2 class="font-serif-fa text-3xl md:text-4xl font-bold mb-6">سالنی برای حسِ خاص‌بودن</h2>
                    <p class="text-m-tx/75 leading-8 mb-6">
                        {{ $currentSalonBio }}
                    </p>
                    {{-- بسته‌ی مجوز 5د: ادعاهای ثابت («گواهینامه‌های بین‌المللی»، «محصولات ارگانیک») برای همه‌ی سالن‌ها حذف شد؛ فقط معرفی خود سالن --}}
                </div>
                {{-- بسته‌ی مجوز 5د: پیش‌تر عکس Unsplash با عنوان «فضای این سالن» --}}
                <div class="fade-up fade-up-delay-2 order-1 lg:order-2 flex items-center justify-center">
                    <x-brand-emblem class="h-40 w-auto max-w-[260px] opacity-90" loading="lazy" />
                </div>
            </div>
        </section>

        {{-- =========================================================
             6) Experts section
        ========================================================= --}}
        <section id="specialists" class="py-20 px-4 max-w-7xl mx-auto">
            <div class="text-center mb-14 fade-up">
                <h2 class="font-serif-fa text-3xl md:text-5xl font-bold text-m-gold2 mb-4">متخصصین ما</h2>
                <p class="text-m-tx/70 max-w-2xl mx-auto leading-8">
                    با تیمی از حرفه‌ای‌ترین متخصصان زیبایی آشنا شوید.
                </p>
                <a href="{{ route('specialists.search') }}" class="inline-block mt-4 text-sm underline text-m-gold2">مشاهده‌ی همه‌ی متخصص‌ها</a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse(($specialists ?? []) as $index => $specialist)
                    <div class="fade-up fade-up-delay-{{ ($index % 3) + 1 }} group relative rounded-2xl overflow-hidden card-hover bg-m-card border border-m-gold/10 flex flex-col items-center justify-center text-center py-10 px-4 h-72">
                        {{-- ⭐ عکس پروفایل متخصص (۲۰۲۶-۰۹-۲۴)، وگرنه حرف اول اسم. --}}
                        @if ($specialist->photoUrl())
                            <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" loading="lazy"
                                 class="w-24 h-24 rounded-full object-cover mb-4 border-2 border-m-gold/40">
                        @else
                            <div class="w-20 h-20 rounded-full bg-m-gold/15 flex items-center justify-center mb-4 text-2xl font-serif-fa font-bold text-m-gold2">
                                {{ mb_substr($specialist->name, 0, 1) }}
                            </div>
                        @endif
                        <h3 class="font-serif-fa font-bold text-lg mb-1">
                            <a href="{{ route('specialists.show', $specialist) }}" class="hover:text-m-gold2">{{ $specialist->name }}</a>
                        </h3>
                        <p class="text-xs text-m-gold2 mb-3">متخصص زیبایی</p>
                        <a href="{{ route('specialists.show', $specialist) }}" class="text-xs underline text-m-tx/70">مشاهده‌ی پروفایل</a>
                        {{-- ⭐ ۲۰۲۶-۰۹-۲۴: شماره موبایل شخصی متخصص (همون شماره‌ی ورودش) دیگه در صفحه‌ی عمومی نمایش داده نمی‌شه. --}}
                    </div>
                @empty
                    {{-- بسته‌ی مجوز 5د: پیش‌تر چهار متخصص ساختگی («زهرا احمدی»…) با عکس Unsplash --}}
                    <p class="col-span-full text-center text-m-tx/60" data-no-specialists>معرفی متخصص‌های این سالن به‌زودی اضافه می‌شود.</p>
                @endforelse
            </div>
        </section>

        {{-- =========================================================
             7) Customer comments section (Testimonials)
        ========================================================= --}}
        {{-- ⭐ بسته‌ی مجوز 5د: فقط نظرهای تأییدشده‌ی واقعی (نام کوتاه‌شده)؛ بی نظر، بخش نمایش داده نمی‌شود --}}
        @if (count($testimonials ?? []) > 0)
        <section id="testimonials" class="py-20 px-4 bg-m-card">
            <div class="max-w-3xl mx-auto text-center mb-12 fade-up">
                <h2 class="font-serif-fa text-3xl md:text-5xl font-bold text-m-gold2 mb-4">نظرات مشتریان</h2>
                <p class="text-m-tx/70 leading-8">آنچه مشتریان عزیز ما درباره تجربه‌شان می‌گویند.</p>
            </div>

            <div class="max-w-2xl mx-auto fade-up">
                <div id="testimonial-track" class="relative overflow-hidden min-h-[260px]">


                    @foreach($testimonials as $index => $t)
                        <div class="testimonial-slide {{ $index === 0 ? '' : 'hidden' }} bg-m-bg rounded-3xl p-8 text-center border border-m-gold/10" data-slide="{{ $index }}">
                            <div class="w-16 h-16 rounded-full mx-auto mb-4 border-2 border-m-gold flex items-center justify-center font-bold text-m-gold2 text-xl" aria-hidden="true">{{ mb_substr($t->name, 0, 1) }}</div>
                            <div class="flex justify-center gap-1 mb-4 text-m-gold2">
                                @for($s = 1; $s <= 5; $s++)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $s <= ($t->rating ?? 5) ? '' : 'opacity-25' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8L1.6 7.7l5.8-.8z"/></svg>
                                @endfor
                            </div>
                            <p class="text-m-tx/85 leading-8 mb-4">{{ $t->text }}</p>
                            <h4 class="font-serif-fa font-bold text-m-gold2">{{ $t->name }}</h4>
                        </div>
                    @endforeach
                </div>


                <div class="flex items-center justify-center gap-6 mt-8">
                    <button id="testimonial-prev" class="w-10 h-10 rounded-full border border-m-gold/40 flex items-center justify-center hover:bg-m-gold/15 transition-colors" aria-label="نظر قبلی">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <div id="testimonial-bullets" class="flex gap-2"></div>
                    <button id="testimonial-next" class="w-10 h-10 rounded-full border border-m-gold/40 flex items-center justify-center hover:bg-m-gold/15 transition-colors" aria-label="نظر بعدی">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                </div>
            </div>
        </section>
        @endif

        {{-- =========================================================
             8) Gallery
        ========================================================= --}}
        {{-- ⭐ بسته‌ی مجوز 5د: فقط عکس‌های گالری خود سالن (پیش‌تر عکس‌های Unsplash با عنوان «نمونه‌ی کارهای این سالن») --}}
        @if (count($galleryImages ?? []) > 0)
        <section id="gallery" class="py-20 px-4 max-w-7xl mx-auto">
            <div class="text-center mb-12 fade-up">
                <h2 class="font-serif-fa text-3xl md:text-5xl font-bold text-m-gold2 mb-4">گالری تصاویر</h2>
                <p class="text-m-tx/70 max-w-2xl mx-auto leading-8">نمونه‌ای از کارهای انجام‌شده در {{ $currentSalonTitle }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">


                @foreach($galleryImages as $index => $img)
                    <a href="{{ $img->instagram_link ?? '#' }}" target="{{ isset($img->instagram_link) ? '_blank' : '_self' }}" class="gallery-item relative aspect-square rounded-xl overflow-hidden block fade-up fade-up-delay-{{ ($index % 3) + 1 }}">
                        <img src="{{ $img->image }}" alt="نمونه کار {{ $currentSalonTitle }}" loading="lazy" class="w-full h-full object-cover">
                        @if(isset($img->instagram_link))
                            <div class="overlay absolute inset-0 bg-black/40 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.25.07 1.62.07 4.81s-.01 3.56-.07 4.81c-.15 3.23-1.66 4.77-4.92 4.92-1.25.06-1.62.07-4.85.07s-3.6 0-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.25-.07-1.62-.07-4.81s.01-3.56.07-4.81c.15-3.23 1.67-4.77 4.92-4.92C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.14 0-3.5.01-4.74.07-2.27.1-3.39 1.24-3.49 3.49C3.7 8.8 3.7 9.16 3.7 12s0 3.2.06 4.44c.1 2.25 1.22 3.4 3.49 3.49 1.24.06 1.6.07 4.74.07s3.5-.01 4.74-.07c2.27-.1 3.39-1.24 3.49-3.49.06-1.24.07-1.6.07-4.44s0-3.2-.06-4.44c-.1-2.25-1.23-3.39-3.49-3.49C15.5 4.01 15.14 4 12 4zm0 3.4a4.6 4.6 0 110 9.2 4.6 4.6 0 010-9.2zm0 1.8a2.8 2.8 0 100 5.6 2.8 2.8 0 000-5.6zm5.85-3.05a1.1 1.1 0 110 2.2 1.1 1.1 0 010-2.2z"/></svg>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- =========================================================
             9) Final CTA section
        ========================================================= --}}
        <section class="cta-bg py-28 px-4 text-center fade-up">
            <h2 class="font-serif-fa text-3xl md:text-5xl font-bold mb-6 text-m-tx">
                وقت آن رسیده که به خودتان برسید
            </h2>
            <p class="text-m-tx/75 max-w-xl mx-auto mb-10 leading-8">
                همین حالا نوبت خود را رزرو کنید و تجربه‌ای متفاوت از زیبایی و آرامش را در {{ $currentSalonTitle }} تجربه کنید.
            </p>
            <a href="{{ route('bookings.create') }}" class="btn-gold inline-block rounded-full px-10 py-4 font-bold text-lg">
                همین حالا رزرو کنید
            </a>
        </section>

    </div>

    @if ($appSalon = app(\App\Support\CurrentSalon::class)->get())
        @include('partials.open-in-app', ['appSalon' => $appSalon])
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ---------- Navbar scroll effect ---------- */

            /* ---------- Fade-up on scroll (Intersection Observer) ---------- */
            const fadeEls = document.querySelectorAll('.fade-up');
            const fadeObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        fadeObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });
            fadeEls.forEach(el => fadeObserver.observe(el));

            /* ---------- Counters (count-up) ---------- */
            const counters = document.querySelectorAll('.counter');
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    const target = parseFloat(el.dataset.target);
                    const isFloat = target % 1 !== 0;
                    const duration = 1500;
                    const startTime = performance.now();

                    const step = (now) => {
                        const progress = Math.min((now - startTime) / duration, 1);
                        const value = target * progress;
                        el.textContent = isFloat ? value.toFixed(1) : Math.floor(value).toLocaleString('fa-IR');
                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            el.textContent = isFloat ? target.toFixed(1) : target.toLocaleString('fa-IR');
                        }
                    };
                    requestAnimationFrame(step);
                    counterObserver.unobserve(el);
                });
            }, { threshold: 0.4 });
            counters.forEach(el => counterObserver.observe(el));

            /* ---------- Testimonials slider ---------- */
            // بخش نظرها فقط وقتی نظر واقعی هست رندر می‌شود (بسته‌ی مجوز 5د)
            if (document.getElementById('testimonial-bullets')) {
                const slides = document.querySelectorAll('.testimonial-slide');
                const bulletsWrap = document.getElementById('testimonial-bullets');
                let current = 0;

                slides.forEach((_, i) => {
                    const bullet = document.createElement('button');
                    bullet.className = 'w-2.5 h-2.5 rounded-full transition-colors ' + (i === 0 ? 'bg-m-gold2' : 'bg-m-gold/30');
                    bullet.setAttribute('aria-label', 'نظر شماره ' + (i + 1));
                    bullet.addEventListener('click', () => showSlide(i));
                    bulletsWrap.appendChild(bullet);
                });

                const bullets = bulletsWrap.querySelectorAll('button');

                function showSlide(index) {
                    if (index < 0) index = slides.length - 1;
                    if (index >= slides.length) index = 0;

                    slides[current].classList.add('hidden');
                    bullets[current].classList.remove('bg-m-gold2');
                    bullets[current].classList.add('bg-m-gold/30');

                    current = index;

                    slides[current].classList.remove('hidden');
                    bullets[current].classList.add('bg-m-gold2');
                    bullets[current].classList.remove('bg-m-gold/30');
                }

                document.getElementById('testimonial-next')?.addEventListener('click', () => showSlide(current + 1));
                document.getElementById('testimonial-prev')?.addEventListener('click', () => showSlide(current - 1));

                // Auto-play comments every 6 seconds
                if (slides.length > 1) {
                    setInterval(() => showSlide(current + 1), 6000);
                }
            }
        });
    </script>
@endpush
