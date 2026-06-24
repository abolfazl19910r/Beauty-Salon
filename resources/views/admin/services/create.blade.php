@extends('layouts.admin')
@section('title', 'افزودن خدمت جدید')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input,.form-select,.form-textarea { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
        .upload-zone { border:2px dashed var(--admin-border); border-radius:10px; padding:2rem; text-align:center; cursor:pointer; transition:border-color 0.15s, background 0.15s; }
        .upload-zone:hover { border-color:var(--admin-accent); background:var(--admin-accent-light); }
    </style>
@endpush

@section('content')
    <div class="fade-in">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن خدمت جدید
            </h1>
            <a href="{{ route('admin.services.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2">
                <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات خدمت</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">نام خدمت <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required placeholder="نام خدمت را وارد کنید">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">دسته‌بندی</label>
                                <select name="category_id" class="form-select">
                                    <option value="">بدون دسته‌بندی</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">توضیحات</label>
                                <textarea name="description" rows="4" class="form-textarea" placeholder="توضیحات مختصر درباره این خدمت...">{{ old('description') }}</textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">قیمت (تومان) <span style="color:#DC2626;">*</span></label>
                                    <input type="number" name="price" value="{{ old('price') }}" class="form-input" required placeholder="0" min="0">
                                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">مدت زمان (دقیقه) <span style="color:#DC2626;">*</span></label>
                                    <input type="number" name="duration" value="{{ old('duration') }}" class="form-input" required placeholder="60" min="1">
                                    @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تصویر خدمت</h2>
                        <label for="image" class="upload-zone block">
                            <svg class="w-10 h-10 mx-auto mb-3" style="color:var(--admin-text-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                            </svg>
                            <p class="text-sm font-medium" style="color:var(--admin-accent);" id="file-name-display">انتخاب تصویر</p>
                            <p class="text-xs mt-1" style="color:var(--admin-text-light);">PNG، JPG تا ۲ مگابایت</p>
                            <input type="file" id="image" name="image" class="hidden" accept="image/*">
                        </label>
                        @error('image') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-between p-4 rounded-xl" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white"
                                style="background:var(--admin-accent);"
                                onmouseover="this.style.background='var(--admin-accent-hover)'"
                                onmouseout="this.style.background='var(--admin-accent)'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                            </svg>
                            ذخیره خدمت
                        </button>
                        <a href="{{ route('admin.services.index') }}"
                           class="px-6 py-2.5 rounded-lg text-sm font-medium"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                    </div>
                </form>
            </div>

            {{-- راهنما --}}
            <div>
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-3" style="color:var(--admin-text);">راهنما</h2>
                    <div class="space-y-3 text-sm" style="color:var(--admin-text-dim);">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#3B82F6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span>نام و قیمت و مدت زمان الزامی هستند.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#3B82F6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span>پس از ساخت خدمت می‌توانید آن را به متخصصین اختصاص دهید.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#F59E0B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>قیمت پیش‌پرداخت توسط مشتریان پرداخت می‌شود.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('image')?.addEventListener('change', function() {
            const name = this.files[0]?.name;
            if (name) document.getElementById('file-name-display').textContent = name;
        });
    </script>
@endpush
