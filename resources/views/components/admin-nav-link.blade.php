@props(['active', 'href'])

@php
    $classes = 'block px-4 py-2 text-sm transition-colors ' .
        ($active
            ? 'bg-gray-900 text-white'
            : 'text-gray-300 hover:bg-gray-700 hover:text-white');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
