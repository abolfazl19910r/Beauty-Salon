@extends('layouts.specialist')

@section('title', 'اعلانات')

@section('content')
    <div class="max-w-5xl mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <svg class="w-6 h-6 ml-2 text-pink-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                اعلانات من
            </h1>

            @if($notifications->total() > 0)
                <form method="POST" action="{{ route('specialist.notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="text-sm text-pink-600 hover:text-pink-700 transition-colors">
                        علامت‌گذاری همه به عنوان خوانده شده
                    </button>
                </form>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($notifications->isEmpty())
                <div class="text-center py-16">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <p class="text-gray-500">هیچ اعلانی وجود ندارد</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($notifications as $notification)
                        <a href="{{ $notification->data['link'] ?? '#' }}"
                           class="block px-6 py-4 hover:bg-gray-50 transition-colors {{ $notification->read_at ? 'bg-white' : 'bg-pink-50' }}">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    @if(!$notification->read_at)
                                        <span class="inline-block w-2 h-2 bg-pink-500 rounded-full mt-2"></span>
                                    @else
                                        <span class="inline-block w-2 h-2 bg-gray-300 rounded-full mt-2"></span>
                                    @endif
                                </div>

                                <div class="mr-3 flex-1">
                                    <p class="text-sm {{ $notification->read_at ? 'text-gray-600' : 'text-gray-900 font-semibold' }}">
                                        {{ $notification->data['message'] ?? 'اعلان جدید' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($notification->created_at)->format('Y/m/d H:i') }}
                                    </p>
                                </div>

                                @if(!$notification->read_at)
                                    <form method="POST" action="{{ route('specialist.notifications.read', $notification->id) }}" class="mr-2">
                                        @csrf
                                        <button type="submit" class="text-xs text-pink-600 hover:text-pink-700">
                                            خوانده شد
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="px-6 py-4 bg-gray-50">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
