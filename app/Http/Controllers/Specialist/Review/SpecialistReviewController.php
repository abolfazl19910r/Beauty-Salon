<?php

namespace App\Http\Controllers\Specialist\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialist\RespondReviewRequest;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Review\ReviewService;
use App\Traits\HasJalaliDates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SpecialistReviewController extends Controller
{
    use HasJalaliDates;

    public function __construct(
        protected readonly ReviewService $reviewService,
        protected readonly ReviewRepositoryInterface $reviewRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $specialist = $this->specialistRepository->findByPhoneOrFail($user->phone);

        $filters = $request->only(['rating', 'responded', 'sort_by']);

        if ($request->filled('date_from')) {
            if ($dateFrom = $this->parseJalali($request->date_from, context: 'تاریخ از')) {
                $filters['date_from'] = $dateFrom->startOfDay();
            }
        }

        if ($request->filled('date_to')) {
            if ($dateTo = $this->parseJalali($request->date_to, context: 'تاریخ تا')) {
                $filters['date_to'] = $dateTo->endOfDay();
            }
        }

        $reviews = $this->reviewRepository->paginateForSpecialistWithFilters($specialist->id, $filters, 10);
        $stats = $this->reviewRepository->getSpecialistStats($specialist->id);
        $averageRating = $this->reviewService->getSpecialistAverageRating($specialist->id);

        return view('specialist.reviews.index', compact(
            'specialist',
            'reviews',
            'stats',
            'averageRating'
        ));
    }

    public function show(Review $review): View
    {
        $user = auth()->user();
        $specialist = $this->specialistRepository->findByPhoneOrFail($user->phone);
        $this->authorize('view', $review);

        $review->load(['user', 'service', 'booking']);

        return view('specialist.reviews.show', compact('review', 'specialist'));
    }

    public function respond(RespondReviewRequest $request, Review $review): RedirectResponse
    {
        $user = auth()->user();
        $specialist = $this->specialistRepository->findByPhoneOrFail($user->phone);
        $this->authorize('respond', $review);

        if ($review->hasResponse()) {
            return back()->with('error', 'شما قبلاً به این نظر پاسخ داده‌اید.');
        }

        $validated = $request->validated();

        try {
            $this->reviewService->respondToReview($review, $validated['response']);

            return back()->with('success', '✅ پاسخ شما با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            Log::error('خطا در ثبت پاسخ به نظر', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در ثبت پاسخ. لطفاً دوباره تلاش کنید.');
        }
    }

    public function updateResponse(RespondReviewRequest $request, Review $review): RedirectResponse
    {
        $user = auth()->user();
        $specialist = $this->specialistRepository->findByPhoneOrFail($user->phone);

        if ($review->specialist_id !== $specialist->id) {
            $this->authorize('respond', $review);
        }

        $validated = $request->validated();

        try {
            $this->reviewRepository->update($review, [
                'specialist_response' => $validated['response'],
                'responded_at' => now(),
            ]);

            return back()->with('success', '✅ پاسخ شما به‌روزرسانی شد.');

        } catch (\Exception $e) {
            Log::error('خطا در ویرایش پاسخ', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در ویرایش پاسخ.');
        }
    }

    public function deleteResponse(Review $review): RedirectResponse
    {
        $user = auth()->user();
        $specialist = $this->specialistRepository->findByPhoneOrFail($user->phone);

        if ($review->specialist_id !== $specialist->id) {
            $this->authorize('respond', $review);
        }

        try {
            $this->reviewRepository->update($review, [
                'specialist_response' => null,
                'responded_at' => null,
            ]);

            return back()->with('success', 'پاسخ با موفقیت حذف شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در حذف پاسخ.');
        }
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $specialist = $this->specialistRepository->findByPhoneOrFail($user->phone);

        $stats = $this->reviewRepository->getSpecialistStats($specialist->id);

        return response()->json($stats);
    }
}
