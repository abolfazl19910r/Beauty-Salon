@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <a href="{{ route('blog.index') }}" class="text-blue-500 hover:text-blue-600">
                    بازگشت به لیست مقالات
                </a>
            </div>

            <article class="bg-white rounded-lg shadow-lg overflow-hidden">
                @if($post->image)
                    <img src="{{ $post->image_url }}"
                         alt="{{ $post->title }}"
                         class="w-full h-64 object-cover">
                @endif

                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-4">{{ $post->title }}</h1>

                    <div class="flex items-center text-gray-600 text-sm mb-6">
                        <span>{{ $post->published_at->format('Y/m/d') }}</span>
                        <span class="mx-2">•</span>
                        <span>{{ $post->category->name }}</span>
                    </div>

                    <div class="prose max-w-none">
                        {!! $post->content !!}
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection
