@props(['tone' => 'accent'])
{{-- برچسب قرصی پنل ابرمدیر (بسته‌ی ۱ ظاهری): success / accent / warn / danger / muted --}}
<span {{ $attributes->class(['sa-pill', 'sa-pill--'.$tone]) }}>{{ $slot }}</span>
