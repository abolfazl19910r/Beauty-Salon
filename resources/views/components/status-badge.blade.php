@props(['status'])

@php
    $classes = match($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };

    $text = match($status) {
        'pending' => 'در انتظار تایید',
        'confirmed' => 'تایید شده',
        'cancelled' => 'لغو شده',
        default => $status
    };
@endphp

<span {{ $attributes->merge(['class' => "px-2 py-1 rounded text-sm $classes"]) }}>
    {{ $text }}
</span>
