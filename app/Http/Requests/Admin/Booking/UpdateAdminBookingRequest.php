<?php

namespace App\Http\Requests\Admin\Booking;

use App\Traits\HasJalaliDates;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The old controller would fork the update() method between two different behaviors:
 * 1) Status change only (from the list/quickcards table, no user_id)
 * 2) Full turn editing (from the edit form)
 *
 * Since both are on the same route/method, this Form Request covers both cases
 * and isStatusOnly() is used to detect the fork in the controller —
 * exactly the same condition that was previously inline in the controller ($request->has('status') && !$request->has('user_id')).
 */
class UpdateAdminBookingRequest extends FormRequest
{
    use HasJalaliDates;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('booking_time')) {
            $this->merge([
                'booking_time' => $this->normalizeToEnglishDigits($this->input('booking_time')),
            ]);
        }
    }

    public function isStatusOnly(): bool
    {
        return $this->has('status') && ! $this->has('user_id');
    }

    public function rules(): array
    {
        if ($this->isStatusOnly()) {
            return [
                'status' => ['required', 'in:pending,confirmed,cancelled'],
            ];
        }

        return [
            'user_id' => ['required', 'exists:users,id'],
            'service_id' => ['required', 'exists:beauty_services,id'],
            'specialist_id' => ['required', 'exists:specialists,id'],
            'booking_time' => ['required', 'date'],
            'status' => ['required', 'in:pending,confirmed,cancelled'],
            'payment_status' => ['required', 'in:paid,unpaid'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * ⭐ Fix (fix/admin-booking-slot-conflict, commit 4): a booking that's already been paid has
     * financial/discount/notification history tied to its current customer — changing user_id
     * afterward would leave that history pointing at the wrong person. Locked here rather than
     * in the service layer so the admin sees the rejection as an ordinary validation error next
     * to the field, before anything is touched.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->isStatusOnly()) {
                return;
            }

            $booking = $this->route('booking');

            if (
                $booking
                && $booking->payment_status === 'paid'
                && (int) $this->input('user_id') !== (int) $booking->user_id
            ) {
                $validator->errors()->add(
                    'user_id',
                    'این نوبت پرداخت شده است — تغییر مشتری روی نوبت پرداخت‌شده ممکن نیست.'
                );
            }
        });
    }
}
