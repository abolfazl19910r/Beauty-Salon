@extends('layouts.app')

@section('title', 'ثبت شکایت')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <h1 class="text-2xl font-bold text-m-gold2 mb-2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">ثبت شکایت</h1>
        <p class="text-sm text-m-tx/60 mb-6 leading-7">
            شکایت شما فقط به مدیر پلتفرم {{ config('brand.name') }} می‌رسد (نه به سالن) و حداکثر ظرف {{ to_persian_num((string) $hours) }} ساعت پاسخ داده می‌شود.
            برای پرسش عادی درباره‌ی نوبت، مستقیم با سالن تماس بگیرید.
        </p>

        <form method="POST" action="{{ route('complaints.store') }}" class="bg-m-card rounded-2xl border border-m-gold/10 p-6 space-y-5">
            @csrf
            <fieldset>
                <legend class="text-sm font-bold mb-2">موضوع</legend>
                @foreach ($targets as $key => $label)
                    <label class="flex items-center gap-2 text-sm py-1">
                        <input type="radio" name="target" value="{{ $key }}" required @checked(old('target', 'complaint_salon') === $key)> {{ $label }}
                    </label>
                @endforeach
                <x-input-error :messages="$errors->get('target')" />
            </fieldset>
            <div>
                <x-input-label for="title" value="عنوان" />
                <x-text-input id="title" name="title" value="{{ old('title') }}" maxlength="150" required class="w-full" />
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div>
                <x-input-label for="description" value="شرح (تاریخ و ساعت نوبت، نام متخصص، آنچه پیش آمد)" />
                <textarea id="description" name="description" rows="6" maxlength="3000" required
                          class="w-full rounded-lg bg-m-bg border border-m-gold/20 p-3 text-sm">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" />
            </div>
            <button type="submit" class="w-full py-3 rounded-lg font-semibold text-sm bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold">ثبت شکایت</button>
        </form>
    </div>
@endsection
