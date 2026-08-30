<?php

namespace App\Http\Requests\Admin\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'service_id' => ['required', 'exists:beauty_services,id'],
            'specialist_id' => ['required', 'exists:specialists,id'],
            'booking_time' => ['required', 'date'],
            'status' => ['required', 'in:pending,confirmed,cancelled'],
            'payment_status' => ['required', 'in:paid,unpaid'],
            // ⭐ Fix (fix/admin-booking-slot-conflict, commit 2): this form is only reachable from
            // the admin panel, so a booking created through it is by definition never 'online' —
            // the customer-facing flow (BookingService::createBooking()) is the only online path
            // and never touches this request. Defaulting to 'phone' keeps existing manual-entry
            // muscle memory working for admins who don't think about the distinction, while still
            // letting them pick 'walk_in' explicitly.
            'source' => ['required', 'in:phone,walk_in'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('source')) {
            $this->merge(['source' => 'phone']);
        }
    }
}
