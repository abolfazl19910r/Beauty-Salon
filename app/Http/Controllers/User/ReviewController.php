<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Review\StoreReviewRequest;
use App\Models\Specialist;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\ReviewTokenRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Review\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        protected readonly ReviewService $reviewService,
        protected readonly ReviewTokenRepositoryInterface $reviewTokenRepository,
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly ReviewRepositoryInterface $reviewRepository,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        try {
            $token = $request->query('token');

            if (! $token) {
                return redirect()->route('home')
                    ->with('error', 'لینک نظرسنجی معتبر نیست.');
            }

            $reviewToken = $this->reviewTokenRepository->findValidToken($token);

            if (! $reviewToken) {
                Log::warning('❌ Token not found or invalid', [
                    'token' => $token,
                ]);

                return redirect()->route('home')
                    ->with('error', 'لینک نظرسنجی منقضی شده است یا قبلاً استفاده شده.');
            }

            $booking = $this->bookingRepository->findOrFailWithReviewDetails($reviewToken->booking_id);

            if (auth()->check() && $booking->user_id !== auth()->id()) {
                return redirect()->route('home')
                    ->with('error', 'این نظرسنجی متعلق به شما نیست.');
            }

            if ($booking->reviewed_at) {
                return redirect()->route('home')
                    ->with('info', 'شما قبلاً برای این نوبت نظر ثبت کرده‌اید.');
            }

            return view('reviews.create', compact('booking', 'token'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ Booking not found', [
                'token' => $request->query('token'),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('home')
                ->with('error', 'نوبت مورد نظر یافت نشد.');

        } catch (\Exception $e) {
            Log::error('❌ Error loading review form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('home')
                ->with('error', 'خطا در بارگذاری فرم نظرسنجی.');
        }
    }

    public function store(StoreReviewRequest $request): RedirectResponse
    {
        try {
            $token = $request->input('token');

            $tokenData = $this->reviewService->validateToken($token);

            if (! $tokenData) {
                return back()->with('error', 'لینک نظرسنجی معتبر نیست.');
            }

            $booking = $this->bookingRepository->findOrFail($tokenData['booking_id']);

            if ($booking->reviewed_at) {
                return redirect()->route('reviews.thank-you')
                    ->with('info', 'شما قبلاً برای این نوبت نظر ثبت کرده‌اید.');
            }

            $review = $this->reviewService->createReview(
                $request->validated(),
                $booking
            );

            $this->reviewService->consumeToken($token);

            return redirect()->route('reviews.thank-you')
                ->with('success', 'نظر شما با موفقیت ثبت شد. از شما متشکریم!');

        } catch (\Exception $e) {
            Log::error('Error storing review', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'خطا در ثبت نظر. لطفاً دوباره تلاش کنید.')
                ->withInput();
        }
    }

    public function thankYou(): View
    {
        return view('reviews.thank-you');
    }

    public function specialistReviews($specialistId): View
    {
        $specialist = $specialistId instanceof Specialist
            ? $specialistId
            : $this->specialistRepository->findOrFail($specialistId);

        $this->ensureSalonOwnership($specialist->salon_id);

        $reviews = $this->reviewRepository->paginateApprovedForSpecialist($specialist->id, 10);

        $stats = $this->reviewRepository->getSpecialistStats($specialist->id);

        return view('reviews.specialist-reviews', compact('specialist', 'reviews', 'stats'));
    }
}
