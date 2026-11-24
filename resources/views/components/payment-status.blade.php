@props(['booking'])
@php $tone = \App\Support\Bookings\BookingLabels::paymentTone($booking); @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap']) }}
      style="{{ \App\Support\Bookings\BookingLabels::pillStyle($tone) }}" data-payment-status="{{ \App\Support\Bookings\BookingLabels::paymentKey($booking) }}">{{ \App\Support\Bookings\BookingLabels::payment($booking) }}</span>
