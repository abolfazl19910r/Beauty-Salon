@extends('layouts.admin')
@section('title', 'دسته‌بندی‌های وبلاگ')

@section('content')
    <div class="fade-in">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">دسته‌بندی‌های وبلاگ</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.categories.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
                   style="background:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-accent-hover)'"
                   onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    دسته‌بندی جدید
                </a>
                <a href="{{ route('admin.blog.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت به مقالات
                </a>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @if($categories->isEmpty())
                <div class="p-10 text-center text-sm" style="color:var(--admin-text-dim);">
                    هیچ دسته‌بندی‌ای یافت نشد
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead>
                        <tr style="border-bottom:1px solid var(--admin-border)">
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">نام</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">توضیحات</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">تعداد مقالات</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">ترتیب</th>
                            <th class="p-4 font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $category)
                            <tr style="border-bottom:1px solid var(--admin-border)">
                                <td class="p-4 font-medium" style="color:var(--admin-text);">{{ $category->name }}</td>
                                <td class="p-4" style="color:var(--admin-text-dim);">{{ \Illuminate\Support\Str::limit($category->description, 60) ?: '—' }}</td>
                                <td class="p-4 persian-number" style="color:var(--admin-text-dim);">{{ number_format($category->posts_count) }}</td>
                                <td class="p-4 persian-number" style="color:var(--admin-text-dim);">{{ $category->order }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.blog.categories.edit', $category) }}" class="text-xs font-medium" style="color:var(--admin-accent);">ویرایش</a>
                                        <form action="{{ route('admin.blog.categories.destroy', $category) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-confirm-delete class="text-xs font-medium" style="color:#dc2626;">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
