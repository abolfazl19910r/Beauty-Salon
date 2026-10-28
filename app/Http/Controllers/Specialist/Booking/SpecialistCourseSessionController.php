<?php

namespace App\Http\Controllers\Specialist\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CourseSessionPhoto;
use App\Services\Courses\CourseException;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseSessionRecords;
use App\Traits\ResolvesSpecialist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * پرونده‌ی جلسه‌ی دوره برای متخصص (بسته‌ی ب): یادداشت و عکس فقط برای نوبت خودش؛ دیدن عکس‌ها و یادداشت‌های جلسه‌های قبلی همان
 * دوره (روی صفحه‌ی نوبت)؛ ثبت رضایت مشتری برای عکس.
 */
class SpecialistCourseSessionController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(private readonly CourseSessionRecords $records) {}

    public function saveNote(Request $request, Booking $booking): RedirectResponse
    {
        $specialist = $this->own($booking);
        $input = $request->validate(['note' => ['nullable', 'string', 'max:'.\App\Models\CourseSessionNote::MAX_LENGTH]]);

        try {
            $this->records->saveNote($booking, $specialist->id, $input['note'] ?? null, $request->boolean('visible_to_customer'));
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'یادداشت جلسه ذخیره شد.');
    }

    public function uploadPhoto(Request $request, Booking $booking): RedirectResponse
    {
        $this->own($booking);
        $request->validate(['photo' => CourseSessionRecords::PHOTO_RULES, 'kind' => ['required', 'in:before,after']]);

        try {
            $this->records->addPhoto($booking, $request->file('photo'), (string) $request->input('kind'), $request->user());
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'عکس ذخیره شد.');
    }

    public function consent(Request $request, Booking $booking, CourseService $courses): RedirectResponse
    {
        $this->own($booking);
        $courses->recordConsent($this->records->course($booking), $request->user(), $request);

        return back()->with('success', 'رضایت مشتری برای عکس ثبت شد.');
    }

    /** «درخواست رضایت» از خود مشتری (۲۰۲۶-۱۰-۲۸) */
    public function requestConsent(Request $request, Booking $booking, \App\Services\Courses\PhotoConsentService $consents): RedirectResponse
    {
        $this->own($booking);
        try {
            $consents->requestAndSend($this->records->course($booking), $request->user());
        } catch (\App\Services\Courses\CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'درخواست رضایت برای مشتری فرستاده شد؛ پاسخ او در سابقه‌ی همین دوره دیده می‌شود.');
    }

    public function resendConsent(Request $request, Booking $booking, \App\Services\Courses\PhotoConsentService $consents): RedirectResponse
    {
        $this->own($booking);
        try {
            $consents->resendAndSend($this->records->course($booking), $request->user());
        } catch (\App\Services\Courses\CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'درخواست دوباره فرستاده شد (لینک قبلی دیگر کار نمی‌کند).');
    }

    /** عکس هر جلسه‌ی دوره‌ای که این متخصص در آن نوبتی دارد */
    public function photo(CourseSessionPhoto $photo)
    {
        $specialist = $this->resolveSpecialist();
        abort_unless($specialist && Booking::query()->where('customer_course_id', $photo->customer_course_id)->where('specialist_id', $specialist->id)->exists(), 404);

        return $this->records->stream($photo);
    }

    private function own(Booking $booking): \App\Models\Specialist
    {
        $specialist = $this->resolveSpecialist();
        abort_unless($specialist && (int) $booking->specialist_id === (int) $specialist->id && $booking->customer_course_id, 404);

        return $specialist;
    }
}
