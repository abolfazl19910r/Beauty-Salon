@extends('layouts.admin')

@section('title', 'ویرایش پاداش')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                ویرایش پاداش: {{ $reward->title }}
            </h1>
            <div>
                <a href="{{ route('admin.loyalty.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت به مدیریت امتیازات
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden fade-in">
            <div class="p-6">
                <form action="{{ route('admin.loyalty.update', $reward) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">عنوان پاداش</label>
                            <input type="text" name="title" id="title" value="{{ $reward->title }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="required_points" class="block text-sm font-medium text-gray-700">امتیاز مورد نیاز</label>
                            <input type="number" name="required_points" id="required_points" value="{{ $reward->required_points }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">توضیحات</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">{{ $reward->description }}</textarea>
                        </div>
                        <div>
                            <label for="discount_type" class="block text-sm font-medium text-gray-700">نوع تخفیف</label>
                            <select name="discount_type" id="discount_type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="fixed" {{ $reward->discount_type === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                                <option value="percentage" {{ $reward->discount_type === 'percentage' ? 'selected' : '' }}>درصدی</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700">مقدار تخفیف</label>
                            <input type="number" name="discount_amount" id="discount_amount" value="{{ $reward->discount_amount }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="max_uses" class="block text-sm font-medium text-gray-700">حداکثر استفاده</label>
                            <input type="number" name="max_uses" id="max_uses" value="{{ $reward->max_uses }}" min="{{ $reward->used_count }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-center mt-3">
                            <input type="checkbox" name="is_active" id="is_active" {{ $reward->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                            <label for="is_active" class="mr-2 text-sm text-gray-700">پاداش فعال باشد</label>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const discountTypeSelect = document.getElementById('discount_type');
            const discountAmountInput = document.getElementById('discount_amount');

            function updateDiscountTypeHint() {
                const hint = document.getElementById('discount-type-hint');
                if (hint) hint.remove();

                const newHint = document.createElement('p');
                newHint.id = 'discount-type-hint';
                newHint.className = 'text-xs text-gray-500 mt-1';

                if (discountTypeSelect.value === 'percentage') {
                    newHint.innerText = 'مقدار باید بین 1 تا 100 باشد';
                } else {
                    newHint.innerText = 'مقدار به تومان وارد شود';
                }

                discountAmountInput.parentNode.appendChild(newHint);
            }

            if (discountTypeSelect && discountAmountInput) {
                updateDiscountTypeHint();
                discountTypeSelect.addEventListener('change', updateDiscountTypeHint);
            }
        });
    </script>
@endpush
