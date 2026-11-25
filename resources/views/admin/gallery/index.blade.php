@extends('layouts.admin')
@section('title', 'مدیریت گالری')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    مدیریت گالری
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    تصاویر نمونه کارهای سالن — <span class="persian-number">{{ fa_num($imagesCount) }}</span> تصویر، <span class="persian-number">{{ fa_num($usedSpace) }}</span> مگابایت
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(34,197,94,0.1); color: rgb(var(--m-ok));">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(220,38,38,0.1); color: rgb(var(--m-bad));">
                <ul class="list-disc pr-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.gallery.store') }}" method="POST" enctype="multipart/form-data"
              class="rounded-xl p-5 mb-5 grid grid-cols-1 md:grid-cols-3 gap-3 items-end"
              style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @csrf
            <div class="md:col-span-1">
                <label class="block text-sm mb-1" style="color:var(--admin-text);">تصویر</label>
                <x-file-input name="image" accept="image/*" required button-style="border:1px solid var(--admin-border); color:var(--admin-text);" />
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm mb-1" style="color:var(--admin-text);">عنوان</label>
                <input type="text" name="title" required
                       class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm mb-1" style="color:var(--admin-text);">توضیحات (اختیاری)</label>
                <input type="text" name="description"
                       class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm mb-1" style="color:var(--admin-text);">خدمت (اختیاری)</label>
                <select name="service_id" class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text); background:var(--admin-surface);">
                    <option value="">— بدون پیوند —</option>
                    @foreach($serviceOptions as $option)
                        <option value="{{ $option->id }}" @selected(old('service_id') == $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm mb-1" style="color:var(--admin-text);">کارشناس (اختیاری)</label>
                <select name="specialist_id" class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text); background:var(--admin-surface);">
                    <option value="">— بدون پیوند —</option>
                    @foreach($specialistOptions as $option)
                        <option value="{{ $option->id }}" @selected(old('specialist_id') == $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-1">
                <button type="submit" class="w-full px-4 py-2 rounded-lg text-sm text-m-onsolid" style="background-color: var(--admin-accent);">
                    افزودن تصویر
                </button>
            </div>
            <p class="md:col-span-3 text-xs" style="color:var(--admin-text-dim);">
                با پیوند خدمت و کارشناس، قالب «نمایشگاه» نام کارشناس را روی نمونه‌کار می‌نویسد و رزرو همان خدمت با همان کارشناس را پیشنهاد می‌دهد.
            </p>
        </form>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @forelse($images as $image)
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <img src="{{ $image->image_url }}" alt="{{ $image->title }}" class="w-full h-32 object-cover">
                    <div class="p-3">
                        <p class="text-sm font-medium truncate" style="color:var(--admin-text);">{{ $image->title }}</p>
                        @if($image->description)
                            <p class="text-xs mt-0.5 truncate" style="color:var(--admin-text-dim);">{{ $image->description }}</p>
                        @endif
                        <p class="text-xs mt-1 truncate" style="color:var(--admin-text-dim);" data-gallery-link-label>
                            @if($image->service || $image->specialist)
                                {{ collect([$image->service?->name, $image->specialist?->name])->filter()->implode(' · ') }}
                            @else
                                بدون پیوند خدمت/کارشناس
                            @endif
                        </p>
                        <details class="mt-2">
                            <summary class="text-xs cursor-pointer" style="color:var(--admin-accent);">پیوند خدمت و کارشناس</summary>
                            <form action="{{ route('admin.gallery.links', $image) }}" method="POST" class="mt-2 space-y-2">
                                @csrf @method('PUT')
                                <select name="service_id" aria-label="خدمت" class="w-full px-2 py-1.5 rounded-lg text-xs" style="border:1px solid var(--admin-border); color:var(--admin-text); background:var(--admin-surface);">
                                    <option value="">— بدون خدمت —</option>
                                    @foreach($serviceOptions as $option)
                                        <option value="{{ $option->id }}" @selected($image->service_id == $option->id)>{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <select name="specialist_id" aria-label="کارشناس" class="w-full px-2 py-1.5 rounded-lg text-xs" style="border:1px solid var(--admin-border); color:var(--admin-text); background:var(--admin-surface);">
                                    <option value="">— بدون کارشناس —</option>
                                    @foreach($specialistOptions as $option)
                                        <option value="{{ $option->id }}" @selected($image->specialist_id == $option->id)>{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="w-full px-2 py-1.5 rounded-lg text-xs text-m-onsolid" style="background-color: var(--admin-accent);">ذخیره‌ی پیوند</button>
                            </form>
                        </details>
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex gap-1">
                                <form action="{{ route('admin.gallery.move-up', $image) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="text-xs px-2 py-1 rounded" style="border:1px solid var(--admin-border); color:var(--admin-text-dim);">▲</button>
                                </form>
                                <form action="{{ route('admin.gallery.move-down', $image) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="text-xs px-2 py-1 rounded" style="border:1px solid var(--admin-border); color:var(--admin-text-dim);">▼</button>
                                </form>
                            </div>
                            <form action="{{ route('admin.gallery.destroy', $image) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-confirm-delete class="text-xs px-2 py-1 rounded" style="color:rgb(var(--m-bad));">حذف</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12" style="color:var(--admin-text-dim);">
                    هنوز تصویری اضافه نشده است.
                </div>
            @endforelse
        </div>
    </div>
@endsection
