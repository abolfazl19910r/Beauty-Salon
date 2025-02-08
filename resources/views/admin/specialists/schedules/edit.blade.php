@extends('layouts.app')

@section('title', 'مدیریت برنامه کاری')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">مدیریت برنامه کاری {{ $specialist->name }}</h1>
                <a href="{{ route('admin.specialists.show', $specialist) }}"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    بازگشت
                </a>
            </div>

            <form action="{{ route('admin.specialists.schedules.update', $specialist) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    @php
                        $days = [
                            0 => 'یکشنبه',
                            1 => 'دوشنبه',
                            2 => 'سه‌شنبه',
                            3 => 'چهارشنبه',
                            4 => 'پنج‌شنبه',
                            5 => 'جمعه',
                            6 => 'شنبه'
                        ];
                    @endphp

                    @foreach($days as $dayNumber => $dayName)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">{{ $dayName }}</h3>
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                           name="schedules[{{ $dayNumber }}][is_active]"
                                           value="1"
                                           class="rounded"
                                        {{ isset($schedules[$dayNumber]) && $schedules[$dayNumber]->first()->is_active ? 'checked' : '' }}>
                                    <span class="mr-2">فعال</span>
                                </label>
                            </div>

                            <input type="hidden"
                                   name="schedules[{{ $dayNumber }}][day_of_week]"
                                   value="{{ $dayNumber }}">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1">ساعت شروع</label>
                                    <input type="time"
                                           name="schedules[{{ $dayNumber }}][start_time]"
                                           value="{{ optional(optional($schedules[$dayNumber])->first())->start_time }}"
                                           class="w-full border rounded px-3 py-2">
                                </div>
                                <div>
                                    <label class="block mb-1">ساعت پایان</label>
                                    <input type="time"
                                           name="schedules[{{ $dayNumber }}][end_time]"
                                           value="{{ optional(optional($schedules[$dayNumber])->first())->end_time }}"
                                           class="w-full border rounded px-3 py-2">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded">
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
