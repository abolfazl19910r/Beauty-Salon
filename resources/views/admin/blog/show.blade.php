@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto fade-in">
            <div class="flex items-center mb-6 py-4 border-b border-gray-100">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-700 transition-colors">
                    <svg class="w-5 h-5 inline-block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span class="mx-2">خانه</span>
                </a>
                <span class="mx-2 text-gray-500">/</span>
                <a href="{{ route('blog.index') }}" class="text-blue-500 hover:text-blue-700 transition-colors">
                    <span>وبلاگ</span>
                </a>
                <span class="mx-2 text-gray-500">/</span>
                <span class="text-gray-700">{{ $post->title }}</span>
            </div>

            <article class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                @if($post->image)
                    <div class="relative h-80 overflow-hidden">
                        <img src="{{ $post->image_url }}"
                             alt="{{ $post->title }}"
                             class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-500">
                    </div>
                @endif

                <div class="p-8">
                    <div class="mb-8">
                        <h1 class="text-3xl md:text-4xl font-bold mb-4 leading-tight">{{ $post->title }}</h1>

                        <div class="flex items-center text-gray-600 text-sm mb-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span class="persian-number">{{ verta($post->published_at)->format('Y/m/d') }}</span>
                            </div>
                            <span class="mx-3">•</span>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                    <polyline points="2 17 12 22 22 17"></polyline>
                                    <polyline points="2 12 12 17 22 12"></polyline>
                                </svg>
                                <a href="{{ route('blog.category', $post->category) }}" class="hover:text-blue-500 transition-colors">
                                    {{ $post->category->name }}
                                </a>
                            </div>
                            <span class="mx-3">•</span>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <span class="persian-number">{{ $post->views ?? 0 }} بازدید</span>
                            </div>
                        </div>
                    </div>

                    <div class="prose prose-lg max-w-none leading-relaxed">
                        {!! $post->content !!}
                    </div>

                    <div class="mt-12 pt-6 border-t border-gray-100">
                        @if(isset($post->tags) && count($post->tags) > 0)
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold mb-3">برچسب‌ها</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($post->tags as $tag)
                                        <a href="{{ route('blog.tag', $tag) }}" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-full transition-colors">
                                            {{ $tag->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-lg font-semibold mb-3">اشتراک‌گذاری</h3>
                            <div class="flex space-x-4 space-x-reverse">
                                <a href="#" class="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path></svg>
                                </a>
                                <a href="#" class="p-2 bg-blue-400 text-white rounded-full hover:bg-blue-500 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path></svg>
                                </a>
                                <a href="#" class="p-2 bg-green-500 text-white rounded-full hover:bg-green-600 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M3.00977 5.83789C3.00977 5.28561 3.45748 4.83789 4.00977 4.83789H20C20.5523 4.83789 21 5.28561 21 5.83789V17.1621C21 17.7144 20.5523 18.1621 20 18.1621H6.63943L3.29519 20.7175C3.13351 20.8442 2.9247 20.884 2.7248 20.8257C2.52489 20.7673 2.36341 20.6169 2.28661 20.4196C2.24401 20.3142 2.22168 20.2016 2.22168 20.0878V5.83789C2.22168 5.54117 2.37841 5.27398 2.63347 5.13059C2.75151 5.0622 2.88748 5.02237 3.00977 5.01385V5.83789ZM4 6.83789V17.1621H19V6.83789H4Z" clip-rule="evenodd"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </article>


            <div class="mt-8 text-center">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center px-5 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت به لیست مقالات
                </a>
            </div>
        </div>
    </div>
@endsection
