<?php

namespace App\Services\Courses;

use App\Models\Booking;
use App\Models\CourseSessionNote;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * پرونده‌ی هر جلسه‌ی دوره: یادداشت متنی و عکس قبل/بعد (تصمیم ۶). مشترک پنل مدیر، پنل متخصص و API همکار/مشتری.
 * عکس: فقط وقتی رضایت مشتری روی دوره ثبت شده، حداکثر ۴ عکس برای هر جلسه، فقط دیسک خصوصی course_photos.
 */
class CourseSessionRecords
{
    public const PHOTO_RULES = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public function course(Booking $booking): CustomerCourse
    {
        $course = $booking->customer_course_id ? CustomerCourse::withoutGlobalScopes()->find($booking->customer_course_id) : null;
        if (! $course) {
            throw new CourseException('این نوبت جلسه‌ی دوره‌ی درمانی نیست.');
        }

        return $course;
    }

    public function saveNote(Booking $booking, ?int $specialistId, ?string $note, bool $visibleToCustomer): CourseSessionNote
    {
        $course = $this->course($booking);
        $note = trim((string) $note);

        return CourseSessionNote::withoutGlobalScopes()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'salon_id' => $course->salon_id,
                'customer_course_id' => $course->id,
                'specialist_id' => $specialistId ?? $booking->specialist_id,
                'note' => $note === '' ? null : mb_substr($note, 0, CourseSessionNote::MAX_LENGTH),
                'visible_to_customer' => $visibleToCustomer,
            ],
        );
    }

    public function addPhoto(Booking $booking, UploadedFile $file, string $kind, User $by): CourseSessionPhoto
    {
        $course = $this->course($booking);

        if (! $course->hasPhotoConsent()) {
            throw new CourseException('برای ثبت عکس، اول رضایت مشتری را روی این دوره ثبت کنید.');
        }
        if (! array_key_exists($kind, CourseSessionPhoto::KINDS)) {
            throw new CourseException('نوع عکس نامعتبر است.');
        }
        if (CourseSessionPhoto::withoutGlobalScopes()->where('booking_id', $booking->id)->count() >= CourseSessionPhoto::MAX_PER_SESSION) {
            throw new CourseException(sprintf('برای هر جلسه حداکثر %s عکس.', to_persian_num((string) CourseSessionPhoto::MAX_PER_SESSION)));
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs(
            sprintf('salon-%d/course-%d/booking-%d', $course->salon_id, $course->id, $booking->id),
            Str::uuid()->toString().'.'.$extension,
            CourseSessionPhoto::DISK,
        );

        return CourseSessionPhoto::withoutGlobalScopes()->create([
            'salon_id' => $course->salon_id,
            'customer_course_id' => $course->id,
            'booking_id' => $booking->id,
            'kind' => $kind,
            'path' => $path,
            'uploaded_by' => $by->id,
        ]);
    }

    public function stream(CourseSessionPhoto $photo): StreamedResponse
    {
        abort_unless(Storage::disk(CourseSessionPhoto::DISK)->exists($photo->path), 404);

        return Storage::disk(CourseSessionPhoto::DISK)->response($photo->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** یادداشت‌ها و عکس‌های جلسه‌های یک دوره، کلید = booking_id */
    public function recordsFor(CustomerCourse $course, bool $customerView = false): array
    {
        $notes = CourseSessionNote::withoutGlobalScopes()->where('customer_course_id', $course->id)
            ->when($customerView, fn ($q) => $q->where('visible_to_customer', true))
            ->get()->keyBy('booking_id');
        $photos = CourseSessionPhoto::withoutGlobalScopes()->where('customer_course_id', $course->id)
            ->orderBy('id')->get()->groupBy('booking_id');

        return ['notes' => $notes, 'photos' => $photos];
    }
}
