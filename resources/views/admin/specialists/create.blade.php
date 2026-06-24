@extends('layouts.admin')
@section('title', 'افزودن متخصص جدید')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
        .service-checkbox { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; cursor:pointer; transition:background 0.1s; }
        .service-checkbox:hover { background:var(--admin-accent-light); }
        .service-checkbox input[type=checkbox] { accent-color:var(--admin-accent); width:16px; height:16px; flex-shrink:0; }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن متخصص جدید
            </h1>
            <a href="{{ route('admin.specialists.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <form action="{{ route('admin.specialists.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات شخصی</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">نام متخصص <span style="color:#DC2626;">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">شماره تماس <span style="color:#DC2626;">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-input" dir="ltr" placeholder="09123456789" required>
                            @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">ایمیل</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input" dir="ltr" placeholder="example@email.com">
                            @error('email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">خدمات قابل ارائه</h2>
                    @error('services') <p class="form-error mb-3">{{ $message }}</p> @enderror
                    <div class="space-y-4">
                        @foreach($services as $category)
                            <div class="rounded-lg overflow-hidden" style="border:1px solid var(--admin-border);">
                                <button type="button" onclick="toggleCategory(this)"
                                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium text-right"
                                        style="background:var(--admin-accent-light); color:var(--admin-text);">
                                    <span>{{ $category->name }}</span>
                                    <svg class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>
                                <div class="category-body p-3 grid grid-cols-1 sm:grid-cols-2 gap-1">
                                    @foreach($category->services as $service)
                                        <label class="service-checkbox">
                                            <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                                {{ in_array($service->id, old('services', [])) ? 'checked' : '' }}>
                                            <span class="flex-1 text-sm" style="color:var(--admin-text);">{{ $service->name }}</span>
                                            <span class="text-xs persian-number" style="color:var(--admin-text-light);">{{ number_format($service->price) }} ت</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-5 p-4 rounded-xl" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors"
                        style="background:var(--admin-accent);"
                        onmouseover="this.style.background='var(--admin-accent-hover)'"
                        onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                    </svg>
                    ذخیره متخصص
                </button>
                <a href="{{ route('admin.specialists.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium transition-colors"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleCategory(btn) {
            const body = btn.nextElementSibling;
            const icon = btn.querySelector('svg');
            body.style.display = body.style.display === 'none' ? '' : 'none';
            icon.style.transform = body.style.display === 'none' ? 'rotate(-90deg)' : '';
        }
    </script>
@endpush
