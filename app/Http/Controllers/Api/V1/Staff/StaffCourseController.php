<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\CourseSessionNote;
use App\Models\CourseSessionPhoto;
use App\Services\Api\CoursePresenter;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseSessionRecords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * پرونده‌ی جلسه‌ی دوره در اپ «ماهرو همکار» (بسته‌ی ب) — همان قاعده‌های پنل وب متخصص (SpecialistCourseSessionController):
 * یادداشت و عکس فقط روی نوبت خود متخصص؛ دیدن یادداشت‌ها و عکس‌های جلسه‌های قبلی همان دوره؛ ثبت رضایت مشتری برای عکس.
 */
class StaffCourseController extends StaffController
{
    public function __construct(private readonly CourseSessionRecords $records) {}

    /** GET bookings/{id}/course — دوره و همه‌ی جلسه‌هایش با یادداشت و عکس */
    public function show(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->courseBooking($request, $bookingId);
        $course = $this->records->course($booking);
        $bookings = $course->bookings()->withoutGlobalScopes()->with('specialist')->orderBy('booking_time')->get();
        $records = $this->records->recordsFor($course);

        return ApiResponse::success(CoursePresenter::course($course) + [
            'sessions' => $bookings->map(fn (Booking $b) => CoursePresenter::session($b, $records, 'api.v1.staff.course-photos.show', $course))->values(),
            'photo_consent_state' => CoursePresenter::photoConsent($course),
        ]);
    }

    /** POST bookings/{id}/session-note {note?, visible_to_customer?} */
    public function saveNote(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->courseBooking($request, $bookingId);
        $input = $request->validate([
            'note' => ['nullable', 'string', 'max:'.CourseSessionNote::MAX_LENGTH],
            'visible_to_customer' => ['nullable', 'boolean'],
        ]);

        $note = $this->records->saveNote($booking, $this->specialist($request)->id, $input['note'] ?? null, $request->boolean('visible_to_customer'));

        return ApiResponse::success(['note' => $note->note, 'visible_to_customer' => (bool) $note->visible_to_customer]);
    }

    /** POST bookings/{id}/photos (multipart: photo, kind=before|after) */
    public function uploadPhoto(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->courseBooking($request, $bookingId);
        $request->validate(['photo' => CourseSessionRecords::PHOTO_RULES, 'kind' => ['required', 'in:before,after']]);

        $photo = $this->records->addPhoto($booking, $request->file('photo'), (string) $request->input('kind'), $request->user());

        return ApiResponse::success([
            'id' => $photo->id,
            'kind' => $photo->kind,
            'url' => route('api.v1.staff.course-photos.show', ['photoId' => $photo->id]),
        ], [], 201);
    }

    /** POST bookings/{id}/course-consent — رضایت مشتری برای عکس (یک بار برای کل دوره) */
    public function consent(Request $request, int $bookingId, CourseService $courses): JsonResponse
    {
        $booking = $this->courseBooking($request, $bookingId);
        $course = $this->records->course($booking);
        $courses->recordConsent($course, $request->user(), $request);

        return ApiResponse::success(['photo_consent' => true, 'photo_consent_state' => CoursePresenter::photoConsent($course->fresh())]);
    }

    /**
     * POST bookings/{id}/course-consent/request — «درخواست رضایت» (۲۰۲۶-۱۰-۲۸): مشتری خودش در اپ، سایت سالن یا لینک پیامک تصمیم
     * می‌گیرد. قاعده‌ها و خطاها (422 course) در PhotoConsentService.
     */
    public function requestConsent(Request $request, int $bookingId, \App\Services\Courses\PhotoConsentService $consents): JsonResponse
    {
        $course = $this->records->course($this->courseBooking($request, $bookingId));
        $consents->requestAndSend($course, $request->user());

        return ApiResponse::success(['photo_consent_state' => CoursePresenter::photoConsent($course->fresh())]);
    }

    /** POST bookings/{id}/course-consent/resend — «ارسال دوباره» همان درخواست، روزی یک بار */
    public function resendConsent(Request $request, int $bookingId, \App\Services\Courses\PhotoConsentService $consents): JsonResponse
    {
        $course = $this->records->course($this->courseBooking($request, $bookingId));
        $consents->resendAndSend($course, $request->user());

        return ApiResponse::success(['photo_consent_state' => CoursePresenter::photoConsent($course->fresh())]);
    }

    /** GET course-photos/{photoId} — عکس هر جلسه‌ی دوره‌ای که این متخصص در آن نوبتی دارد */
    public function photo(Request $request, int $photoId)
    {
        $photo = CourseSessionPhoto::query()->find($photoId);
        $allowed = $photo && Booking::query()->where('customer_course_id', $photo->customer_course_id)
            ->where('specialist_id', $this->specialist($request)->id)->exists();
        if (! $allowed) {
            throw new ApiException('not_found', 'عکس پیدا نشد.', 404);
        }

        return $this->records->stream($photo);
    }

    private function courseBooking(Request $request, int $bookingId): Booking
    {
        $booking = Booking::query()->whereKey($bookingId)->where('specialist_id', $this->specialist($request)->id)->first();
        if (! $booking) {
            throw new ApiException('not_found', 'نوبت پیدا نشد.', 404);
        }
        if (! $booking->customer_course_id) {
            throw new ApiException('not_course_session', 'این نوبت جلسه‌ی دوره‌ی درمانی نیست.', 422);
        }

        return $booking;
    }
}
