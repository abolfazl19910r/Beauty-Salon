{{-- جعبه‌ی روز نوبت (بسته‌ی ۴ ظاهری): روز هفته، روز ماه درشت، ماه — داشبورد و «نوبت‌های من» --}}
@php $__v = verta($at); @endphp
<span class="shrink-0 w-14 rounded-2xl border border-m-gold/20 bg-m-gold/5 flex flex-col items-center justify-center py-1.5 leading-tight" aria-hidden="true">
    <span class="text-[11px] text-m-tx/60">{{ strtr($__v->format('l'), ['سه شنبه' => 'سه‌شنبه', 'پنج شنبه' => 'پنج‌شنبه']) }}</span>
    <b class="text-xl text-m-tx">{{ fa_num($__v->day) }}</b>
    <span class="text-[11px] text-m-tx/60">{{ $__v->format('F') }}</span>
</span>
