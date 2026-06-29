@extends('layouts.admin')
@section('title', 'اختصاص نقش به کاربر')

@section('content')
    <div class="container px-6 mx-auto max-w-xl">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">اختصاص نقش</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">اختصاص نقش «{{ $role->label }}» به کاربر</p>
            </div>
            <a href="{{ route('admin.roles.show', $role) }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            @if($users->isEmpty())
                <div class="py-12 text-center" style="color:var(--admin-text-dim)">
                    <svg class="w-12 h-12 mx-auto mb-3" style="color:var(--admin-border)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    <p>تمام کاربران سیستم این نقش را دارند</p>
                </div>
            @else
                <form action="{{ route('admin.roles.assign', $role) }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label class="block text-sm font-medium mb-2" style="color:var(--admin-text-dim)">انتخاب کاربر</label>
                        <select name="user_id" required
                                class="w-full rounded-lg px-3 py-2 text-sm"
                                style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            <option value="">انتخاب کنید...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone }})</option>
                            @endforeach
                        </select>
                        @error('user_id')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3 pt-4" style="border-top:1px solid var(--admin-border)">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg"
                                style="background:var(--admin-accent)">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            اختصاص نقش
                        </button>
                        <a href="{{ route('admin.roles.show', $role) }}"
                           class="inline-flex items-center px-5 py-2 text-sm rounded-lg border"
                           style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                            انصراف
                        </a>
                    </div>
                </form>
            @endif
        </div>

    </div>
@endsection
