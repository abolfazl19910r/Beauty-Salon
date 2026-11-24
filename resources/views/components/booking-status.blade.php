@props(['status', 'audience' => 'staff'])
@php $tone = \App\Support\Bookings\BookingLabels::statusTone($status); @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap']) }}
      style="{{ \App\Support\Bookings\BookingLabels::pillStyle($tone) }}" data-booking-status="{{ $status }}">{{ \App\Support\Bookings\BookingLabels::status($status, $audience) }}</span>
