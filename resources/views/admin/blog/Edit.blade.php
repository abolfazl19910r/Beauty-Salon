@extends('layouts.admin')
@section('title', 'ویرایش مقاله')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input,.form-select,.form-textarea { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
    </style>
@endpush

@section('content')
    <div class="fade-in">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">ویرایش مقاله</h1>
            <a href="{{ route('admin.blog.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <form action="{{ route('admin.blog.update', $post) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- ستون اصلی --}}
                <div class="lg:col-span-2 space-y-5">

                    <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">محتوای مقاله</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">عنوان مقاله <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="title" value="{{ old('title', $post->title) }}" class="form-input" required>
                                @error('title') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">چکیده (اختیاری)</label>
                                <textarea name="excerpt" rows="3" class="form-textarea">{{ old('excerpt', $post->excerpt) }}</textarea>
                                @error('excerpt') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">محتوا <span style="color:#DC2626;">*</span></label>
                                <textarea name="content" rows="10" class="form-textarea" required>{{ old('content', $post->content) }}</textarea>
                                @error('content') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تصویر مقاله</h2>
                        <label for="blog-image" class="block border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors"
                               style="border-color:var(--admin-border);"
                               onmouseover="this.style.borderColor='var(--admin-accent)'; this.style.background='var(--admin-accent-light)'"
                               onmouseout="this.style.borderColor='var(--admin-border)'; this.style.background=''">
                            <div id="blog-preview-container" class="mb-3 {{ $post->image_url ? '' : 'hidden' }}">
                                <img id="blog-image-preview" src="{{ $post->image_url }}" class="w-32 h-24 object-cover rounded-lg mx-auto">
                            </div>
                            <svg class="w-8 h-8 mx-auto mb-2 {{ $post->image_url ? 'hidden' : '' }}" id="blog-upload-icon" style="color:var(--admin-text-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                            </svg>
                            <p class="text-sm font-medium" style="color:var(--admin-accent);" id="blog-file-name">{{ $post->image_url ? 'تغییر تصویر' : 'انتخاب تصویر' }}</p>
                            <p class="text-xs mt-1" style="color:var(--admin-text-light);">PNG، JPG تا ۲ مگابایت</p>
                            <input type="file" id="blog-image" name="image" class="hidden" accept="image/*">
                        </label>
                        @error('image') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ستون کناری --}}
                <div class="space-y-5">
                    <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تنظیمات انتشار</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">دسته‌بندی <span style="color:#DC2626;">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">انتخاب دسته‌بندی</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $post->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">تاریخ انتشار</label>
                                <input type="text" name="published_at_jalali" class="form-input"
                                       value="{{ old('published_at_jalali', $post->published_at_jalali) }}"
                                       placeholder="۱۴۰۳/۰۳/۱۵ ۱۴:۳۰">
                                <p class="text-xs mt-1" style="color:var(--admin-text-light);">اگر خالی بماند، تاریخ فعلی مقاله دست‌نخورده می‌ماند.</p>
                                @error('published_at_jalali') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2 p-3 rounded-lg cursor-pointer"
                                   style="border:1px solid var(--admin-border);"
                                   onmouseover="this.style.background='var(--admin-accent-light)'"
                                   onmouseout="this.style.background=''">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}
                                style="accent-color:var(--admin-accent); width:15px; height:15px;">
                                <span class="text-sm" style="color:var(--admin-text);">منتشر شده</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium text-white mb-2"
                                style="background:var(--admin-accent);"
                                onmouseover="this.style.background='var(--admin-accent-hover)'"
                                onmouseout="this.style.background='var(--admin-accent)'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                            </svg>
                            ذخیره تغییرات
                        </button>
                        <a href="{{ route('admin.blog.index') }}"
                           class="w-full flex items-center justify-center py-2.5 rounded-lg text-sm font-medium"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('blog-image')?.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            document.getElementById('blog-file-name').textContent = file.name;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('blog-image-preview').src = e.target.result;
                document.getElementById('blog-preview-container').classList.remove('hidden');
                document.getElementById('blog-upload-icon').classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    </script>
@endpush
