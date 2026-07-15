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
            'user_id'        => ['required', 'exists:users,id'],
            'service_id'     => ['required', 'exists:beauty_services,id'],
            'specialist_id'  => ['required', 'exists:specialists,id'],
            'booking_time'   => ['required', 'date'],
            'status'         => ['required', 'in:pending,confirmed,cancelled'],
            'payment_status' => ['required', 'in:paid,unpaid'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
