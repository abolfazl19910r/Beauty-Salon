<?php

namespace App\Http\Controllers\Admin\Review;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function index(Request $request): View
    {
        $reviews = $this->reviewRepository->paginateWithFilters($request->all(), 15);
        $totalReviews = $this->reviewRepository->count();
        $approvedReviews = $this->reviewRepository->countApproved();
        $negativeReviews = $this->reviewRepository->countNegative();
        $averageRating = round($this->reviewRepository->avgOverallRating() ?? 0, 1);
        $specialists = $this->specialistRepository->getNameOptions();

        return view('admin.reviews.index', compact(
            'reviews',
            'totalReviews',
            'approvedReviews',
            'negativeReviews',
            'averageRating',
            'specialists'
        ));
    }

    public function show(Review $review): View
    {
        $this->ensureReviewInSalon($review);

        $review->load(['user', 'specialist', 'service', 'booking']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $this->ensureReviewInSalon($review);

        try {
            $this->reviewRepository->update($review, ['is_approved' => true]);

            Log::info('Review approved by admin', [
                'review_id' => $review->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', '✅ نظر تایید شد.');

        } catch (\Exception $e) {
            Log::error('خطا در تایید نظر', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در تایید نظر.');
        }
    }

    public function reject(Review $review): RedirectResponse
    {
        $this->ensureReviewInSalon($review);

        try {
            $this->reviewRepository->update($review, ['is_approved' => false]);

            Log::info('Review rejected by admin', [
                'review_id' => $review->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', 'نظر رد شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در رد نظر.');
        }
    }

    public function toggleFeatured(Review $review): RedirectResponse
    {
        $this->ensureReviewInSalon($review);

        try {
            $review = $this->reviewRepository->update($review, ['is_featured' => ! $review->is_featured]);

            $message = $review->is_featured
                ? '⭐ نظر به عنوان ویژه علامت‌گذاری شد.'
                : 'نظر از حالت ویژه خارج شد.';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در تغییر وضعیت نظر.');
        }
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->ensureReviewInSalon($review);

        try {
            $this->reviewRepository->delete($review);

            Log::warning('Review soft deleted by admin', [
                'review_id' => $review->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', '🗑️ نظر حذف شد.');

        } catch (\Exception $e) {
            Log::error('خطا در حذف نظر', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در حذف نظر.');
        }
    }

    public function restore($id): RedirectResponse
    {
        try {
            $review = $this->reviewRepository->findWithTrashedOrFail($id);
            $review->restore();

            return back()->with('success', '♻️ نظر بازگردانی شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بازگردانی نظر.');
        }
    }

    public function forceDelete($id): RedirectResponse
    {
        try {
            $review = $this->reviewRepository->findWithTrashedOrFail($id);
            $review->forceDelete();

            Log::warning('Review permanently deleted by admin', [
                'review_id' => $id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', '⚠️ نظر به طور دائمی حذف شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در حذف دائمی نظر.');
        }
    }

    public function stats(): View
    {
        $totalReviews = $this->reviewRepository->count();
        $averageRating = round($this->reviewRepository->avgOverallRating() ?? 0, 1);
        $ratingDistribution = $this->reviewRepository->getRatingDistribution();
        $topSpecialists = $this->specialistRepository->getTopRatedByApprovedReviews(10);
        $recentNegativeReviews = $this->reviewRepository->getRecentNegative(5);
        $monthlyStats = $this->reviewRepository->getMonthlyStats(12);

        return view('admin.reviews.stats', compact(
            'totalReviews',
            'averageRating',
            'ratingDistribution',
            'topSpecialists',
            'recentNegativeReviews',
            'monthlyStats'
        ));
    }

    public function trashed(): View
    {
        $reviews = $this->reviewRepository->paginateTrashed(15);

        return view('admin.reviews.trashed', compact('reviews'));
    }

    private function ensureReviewInSalon(Review $review): void
    {
        $this->ensureSalonOwnership($this->specialistRepository->getSalonIdIgnoringScopes($review->specialist_id));
    }
}
