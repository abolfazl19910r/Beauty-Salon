@extends('layouts.admin')
@section('title', 'ویرایش خدمت')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input,.form-select,.form-textarea { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
        .upload-zone { border:2px dashed var(--admin-border); border-radius:10px; padding:1.5rem; text-align:center; cursor:pointer; transition:border-color 0.15s, background 0.15s; }
        .upload-zone:hover { border-color:var(--admin-accent); background:var(--admin-accent-light); }
    </style>
@endpush

@section('content')
    <div class="fade-in">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">ویرایش خدمت</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $service->name }}</p>
            </div>
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
                <form action="{{ route('admin.services.update', $service) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات خدمت</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">نام خدمت <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $service->name) }}" class="form-input" required>
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">دسته‌بندی</label>
                                <select name="category_id" class="form-select">
                                    <option value="">بدون دسته‌بندی</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ old('category_id', $service->category_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">توضیحات</label>
                                <textarea name="description" rows="4" class="form-textarea">{{ old('description', $service->description) }}</textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">قیمت (تومان) <span style="color:#DC2626;">*</span></label>
                                    <input type="number" name="price" value="{{ old('price', $service->price) }}" class="form-input" required min="0">
                                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">مدت زمان (دقیقه) <span style="color:#DC2626;">*</span></label>
                                    <input type="number" name="duration" value="{{ old('duration', $service->duration) }}" class="form-input" required min="1">
                                    @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تصویر خدمت</h2>
                        @if($service->image)
                            <div class="mb-4 flex items-center gap-4">
                                <img src="{{ $service->image_url }}" alt="{{ $service->name }}"
                                     class="w-20 h-20 object-cover rounded-lg">
                                <div class="text-sm" style="color:var(--admin-text-dim);">
                                    <p>تصویر فعلی</p>
                                    <p class="text-xs mt-1" style="color:var(--admin-text-light);">برای تغییر، تصویر جدید انتخاب کنید</p>
                                </div>
                            </div>
                        @endif
                        <label for="image" class="upload-zone block">
                            <svg class="w-8 h-8 mx-auto mb-2" style="color:var(--admin-text-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                            </svg>
                            <p class="text-sm font-medium" style="color:var(--admin-accent);" id="file-name-display">
                                {{ $service->image ? 'انتخاب تصویر جدید' : 'انتخاب تصویر' }}
                            </p>
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
                            ذخیره تغییرات
                        </button>
                        <a href="{{ route('admin.services.index') }}"
                           class="px-6 py-2.5 rounded-lg text-sm font-medium"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                    </div>
                </form>
            </div>

            {{-- اطلاعات فعلی --}}
            <div class="space-y-4">
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-3 pb-2" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات فعلی</h2>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span style="color:var(--admin-text-dim);">قیمت:</span>
                            <span class="persian-number font-medium" style="color:#16A34A;">{{ number_format($service->price) }} تومان</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color:var(--admin-text-dim);">مدت:</span>
                            <span class="persian-number" style="color:var(--admin-text);">{{ $service->duration }} دقیقه</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color:var(--admin-text-dim);">دسته‌بندی:</span>
                            <span style="color:var(--admin-text);">{{ $service->category->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color:var(--admin-text-dim);">متخصصین:</span>
                            <span class="persian-number font-bold" style="color:var(--admin-accent);">{{ $service->specialists()->count() }}</span>
                        </div>
                    </div>
                </div>

                @permission('delete-services')
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-3 pb-2" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">عملیات خطرناک</h2>
                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="button" data-confirm-delete data-confirm-message="آیا از حذف خدمت «{{ $service->name }}» اطمینان دارید؟"
                                class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm"
                                style="background:#FEF2F2; color:#991B1B;"
                                onmouseover="this.style.background='#FEE2E2'"
                                onmouseout="this.style.background='#FEF2F2'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                            </svg>
                            حذف این خدمت
                        </button>
                    </form>
                </div>
                @endpermission
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
