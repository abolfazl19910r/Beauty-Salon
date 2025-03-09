@extends('layouts.admin')

@section('title', 'مدیریت متخصصین')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">مدیریت متخصصین</h1>
            <a href="{{ route('admin.specialists.create') }}"
               class="bg-green-500 text-white px-4 py-2 rounded">
                افزودن متخصص جدید
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto" dir="rtl">
            <table class="w-full" dir="rtl">
                <thead>
                <tr class="bg-gray-50 text-right">
                    <th class="px-6 py-3 text-right">نام</th>
                    <th class="px-6 py-3 text-right">شماره تماس</th>
                    <th class="px-6 py-3 text-right">ایمیل</th>
                    <th class="px-6 py-3 text-right">تعداد نوبت‌های امروز</th>
                    <th class="px-6 py-3 text-right">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($specialists as $specialist)
                    <tr>
                        <td class="px-6 py-4 text-right">{{ $specialist->name }}</td>
                        <td class="px-6 py-4 text-right" dir="ltr">{{ $specialist->phone }}</td>
                        <td class="px-6 py-4 text-right">{{ $specialist->email }}</td>
                        <td class="px-6 py-4 text-right">
                            {{ $specialist->bookings()->whereDate('booking_time', today())->count() }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.specialists.edit', $specialist->id) }}"
                               class="text-blue-500">ویرایش</a>
                            <form action="{{ route('admin.specialists.destroy', $specialist->id) }}"
                                  method="POST"
                                  class="inline"
                                  id="delete-form-{{ $specialist->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        class="text-red-500 mr-4"
                                        onclick="deleteSpecialist({{ $specialist->id }})">
                                    حذف
                                </button>
                            </form>
                            @push('scripts')
                                <script>
                                    function deleteSpecialist(id) {
                                        if (confirm('آیا مطمئن هستید که می‌خواهید این متخصص را حذف کنید؟')) {
                                            console.log('Deleting specialist:', id); // برای دیباگ
                                            document.getElementById('delete-form-' + id).submit();
                                        }
                                    }
                                </script>
                            @endpush
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $specialists->links() }}
        </div>
    </div>
@endsection
