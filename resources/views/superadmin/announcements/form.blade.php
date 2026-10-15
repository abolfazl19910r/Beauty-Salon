@extends('layouts.superadmin')

@section('title', $announcement->exists ? 'ویرایش اطلاعیه‌ی سراسری' : 'اطلاعیه‌ی سراسری تازه')

@section('content')
    <form method="POST" action="{{ $announcement->exists ? route('superadmin.announcements.update', $announcement) : route('superadmin.announcements.store') }}" class="sa-card p-6 max-w-2xl space-y-4">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif
        <div>
            <label class="sa-label" for="title">عنوان</label>
            <input id="title" name="title" class="sa-input" maxlength="150" required value="{{ old('title', $announcement->title) }}">
            @error('title')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="sa-label" for="body">متن</label>
            <textarea id="body" name="body" class="sa-input" rows="4" maxlength="2000" required>{{ old('body', $announcement->body) }}</textarea>
            @error('body')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="sa-label" for="audience">مخاطب</label>
                <select id="audience" name="audience" class="sa-input">
                    @foreach (\App\Models\PlatformAnnouncement::AUDIENCES as $key => $label)
                        <option value="{{ $key }}" @selected(old('audience', $announcement->audience) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="level">نوع</label>
                <select id="level" name="level" class="sa-input">
                    @foreach (\App\Models\PlatformAnnouncement::LEVELS as $key => $label)
                        <option value="{{ $key }}" @selected(old('level', $announcement->level) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs mt-1" style="color: var(--sa-text-dim);">«مهم / قطعی» را کاربر نمی‌تواند ببندد.</p>
            </div>
            <div>
                <label class="sa-label" for="starts_at">شروع نمایش (شمسی، اختیاری)</label>
                <input id="starts_at" name="starts_at" class="sa-input" dir="ltr" placeholder="1405/08/01" value="{{ old('starts_at', $announcement->starts_at ? jalali_date($announcement->starts_at) : '') }}">
                @error('starts_at')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="sa-label" for="ends_at">پایان نمایش (شمسی، اختیاری)</label>
                <input id="ends_at" name="ends_at" class="sa-input" dir="ltr" placeholder="1405/08/30" value="{{ old('ends_at', $announcement->ends_at ? jalali_date($announcement->ends_at) : '') }}">
                @error('ends_at')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $announcement->is_active))> فعال
        </label>
        <div class="flex gap-3">
            <button type="submit" class="sa-btn">ذخیره</button>
            <a href="{{ route('superadmin.announcements.index') }}" class="px-4 py-2" style="color: var(--sa-text-dim);">انصراف</a>
        </div>
    </form>
@endsection
