<?php

namespace App\Services\Specialist;

use App\Models\User;

class SpecialistProfileService
{
    public function getProfileShowData(User $user): array
    {
        return [
            'totalBookings'     => $user->bookings()->count(),
            'completedBookings' => $user->bookings()->where('status', 'completed')->count(),
            'cancelledBookings' => $user->bookings()->where('status', 'cancelled')->count(),
            'upcomingBookings'  => $user->bookings()
                ->where('booking_time', '>', now())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with(['service', 'specialist'])
                ->orderBy('booking_time', 'asc')
                ->get(),
            'myBookings'        => $user->bookings()
                ->with(['service', 'specialist'])
                ->latest()
                ->paginate(10),
        ];
    }
}
