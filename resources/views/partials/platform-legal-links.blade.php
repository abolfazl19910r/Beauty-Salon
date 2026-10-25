{{-- بسته‌ی مجوز 5د: سایت هر سالن روی سکوی ماهرو است؛ قوانین، حریم خصوصی و تماس پلتفرم (دامنه‌ی مرکزی) --}}
<p class="mt-2 text-xs flex flex-wrap justify-center gap-x-3 gap-y-1" data-platform-legal-links>
    <span>نوبت‌دهی با {{ config('brand.name') }}</span>
    <a href="{{ \App\Support\CentralUrl::route('legal.terms') }}" class="hover:text-m-gold2 underline">قوانین و شرایط</a>
    <a href="{{ \App\Support\CentralUrl::route('legal.privacy') }}" class="hover:text-m-gold2 underline">حریم خصوصی</a>
    <a href="{{ \App\Support\CentralUrl::route('legal.contact') }}" class="hover:text-m-gold2 underline">تماس با {{ config('brand.name') }}</a>
</p>
