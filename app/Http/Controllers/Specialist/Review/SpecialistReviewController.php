<?php

namespace App\Http\Controllers\Specialist\Review;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Specialist;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpecialistReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->firstOrFail();

        $query = Review::with(['user', 'service', 'booking'])
            ->where('specialist_id', $specialist->id);

        if ($request->filled('rating')) {
            $query->where('overall_rating', $request->rating);
        }

        if ($request->has('responded')) {
            if ($request->responded === '1') {
                $query->whereNotNull('specialist_response');
            } else {
                $query->whereNull('specialist_response');
            }
        }

        if ($request->filled('date_from')) {
            try {
                $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $dateFrom = str_replace($persianDigits, $englishDigits, $request->date_from);

                $dateFrom = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateFrom)
                    ->toCarbon()
                    ->startOfDay();
                $query->where('reviewed_at', '>=', $dateFrom);
            } catch (\Exception $e) {
                Log::warning('خطا در تبدیل تاریخ از: ' . $e->getMessage());
            }
        }

        if ($request->filled('date_to')) {
            try {
                $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $dateTo = str_replace($persianDigits, $englishDigits, $request->date_to);

                $dateTo = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateTo)
                    ->toCarbon()
                    ->endOfDay();
                $query->where('reviewed_at', '<=', $dateTo);
            } catch (\Exception $e) {
                Log::warning('خطا در تبدیل تاریخ تا: ' . $e->getMessage());
            }
        }

        $sortBy = $request->get('sort_by', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest('reviewed_at');
                break;
            case 'highest_rating':
                $query->orderBy('overall_rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('overall_rating', 'asc');
                break;
            default:
                $query->latest('reviewed_at');
        }

        $reviews = $query->paginate(10)->withQueryString();
        $stats = Review::getSpecialistStats($specialist->id);
        $averageRating = $this->reviewService->getSpecialistAverageRating($specialist->id);

        return view('specialist.reviews.index', compact(
            'specialist',
            'reviews',
            'stats',
            'averageRating'
        ));
    }

    public function show(Review $review)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->firstOrFail();
        $this->authorize('view', $review);

        $review->load(['user', 'service', 'booking']);

        return view('specialist.reviews.show', compact('review', 'specialist'));
    }

    public function respond(Request $request, Review $review)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->firstOrFail();
        $this->authorize('respond', $review);

        if ($review->hasResponse()) {
            return back()->with('error', 'شما قبلاً به این نظر پاسخ داده‌اید.');
        }

        $validated = $request->validate([
            'response' => 'required|string|max:1000'
        ], [
            'response.required' => 'لطفاً پاسخ خود را وارد کنید.',
            'response.max' => 'پاسخ شما نباید بیشتر از 1000 کاراکتر باشد.'
        ]);

        try {
            $this->reviewService->respondToReview($review, $validated['response']);

            return back()->with('success', '✅ پاسخ شما با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            Log::error('خطا در ثبت پاسخ به نظر', [
                'review_id' => $review->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در ثبت پاسخ. لطفاً دوباره تلاش کنید.');
        }
    }

    public function updateResponse(Request $request, Review $review)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->firstOrFail();

        if ($review->specialist_id !== $specialist->id) {
            $this->authorize('respond', $review);
        }

        $validated = $request->validate([
            'response' => 'required|string|max:1000'
        ]);

        try {
            $review->update([
                'specialist_response' => $validated['response'],
                'responded_at' => now(),
            ]);

            return back()->with('success', '✅ پاسخ شما به‌روزرسانی شد.');

        } catch (\Exception $e) {
            Log::error('خطا در ویرایش پاسخ', [
                'review_id' => $review->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در ویرایش پاسخ.');
        }
    }

    public function deleteResponse(Review $review)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->firstOrFail();

        if ($review->specialist_id !== $specialist->id) {
            $this->authorize('respond', $review);
        }

        try {
            $review->update([
                'specialist_response' => null,
                'responded_at' => null,
            ]);

            return back()->with('success', 'پاسخ با موفقیت حذف شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در حذف پاسخ.');
        }
    }

    public function stats()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->firstOrFail();

        $stats = Review::getSpecialistStats($specialist->id);

        return response()->json($stats);
    }
}
