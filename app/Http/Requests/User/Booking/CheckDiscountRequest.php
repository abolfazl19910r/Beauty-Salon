<?php

namespace App\Http\Requests\User\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckDiscountRequest extends FormRequest
{
    /**
     * ⭐ Note (test-writing session 9): this endpoint used to be reachable two ways —
     * an unauthenticated-looking route at /api/check-discount (registered in
     * routes/api/public/bookings.php, no middleware) that this authorize() check
     * silently blocked anyway (403 for guests), and a properly auth:sanctum-guarded
     * route at /api/bookings/check-discount (routes/api/user/bookings.php). Per an
     * explicit project decision that discount-code preview really does require login
     * (discount codes can be scoped to a specific user via discount_codes.user_id, so
     * previewing one without an authenticated identity doesn't make sense), the
     * misleading "public" registration was removed rather than loosening this check —
     * routing now honestly reflects the auth requirement instead of quietly enforcing
     * it one layer down. This authorize() check stays as defense-in-depth alongside
     * the route's auth:sanctum middleware.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Previously, service_id was incorrectly required when it wasn't used in the calculation at all
     * and the checkout page wouldn't submit it — result: permanent 422
     * error on discount preview. Both fields are now optional; booking_id has also been added
     * so that BookingDiscountController::resolveBaseAmount() can use the actual
     * advance payment amount of the same booking as the basis for the calculation.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'service_id' => ['nullable', 'exists:beauty_services,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'لطفاً کد تخفیف را وارد کنید.',
            'code.max' => 'کد تخفیف نمی‌تواند بیشتر از ۵۰ کاراکتر باشد.',
            'service_id.exists' => 'سرویس انتخابی معتبر نیست.',
            'booking_id.exists' => 'نوبت انتخابی معتبر نیست.',
        ];
    }
}
