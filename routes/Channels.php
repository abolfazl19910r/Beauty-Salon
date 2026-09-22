<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Security.Alerts.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('App.Security.Activity.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId && $user->hasPermission('view_security_logs');
});

Broadcast::channel('App.TwoFactor.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('App.Payments.Status.{paymentId}', function ($user, $paymentId) {
    $payment = app(\App\Repositories\Contracts\PaymentRepositoryInterface::class)->find($paymentId);

    return $payment && $payment->booking->user_id === $user->id;
});

Broadcast::channel('App.Bookings.Updates.{bookingId}', function ($user, $bookingId) {
    $booking = app(\App\Repositories\Contracts\BookingRepositoryInterface::class)->find($bookingId);

    return $booking && ($booking->user_id === $user->id || $user->isAdmin());
});

Broadcast::channel('App.Specialists.Schedule.{specialistId}', function ($user, $specialistId) {
    return $user->isAdmin() || (int) $user->specialist_id === (int) $specialistId;
});

Broadcast::channel('App.Admin.Dashboard', function ($user) {
    return $user->isAdmin();
});

Broadcast::channel('App.Notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('private-user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
