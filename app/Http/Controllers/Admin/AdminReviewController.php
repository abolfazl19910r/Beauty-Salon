<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'specialist', 'service', 'booking']);

        if ($request->filled('specialist_id')) {
            $query->where('specialist_id', $request->specialist_id);
        }

        if ($request->filled('rating')) {
            $query->where('overall_rating', $request->rating);
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->is_approved === '1');
        }

        if ($request->has('negative')) {
            $query->negative();
        }

        if ($request->has('has_response')) {
            if ($request->has_response === '1') {
                $query->whereNotNull('specialist_response');
            } else {
                $query->whereNull('specialist_response');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('specialist', function($specQuery) use ($search) {
                        $specQuery->where('name', 'like', "%{$search}%");
                    });
            });
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

        $reviews = $query->paginate(15)->withQueryString();
        $totalReviews = Review::count();
        $approvedReviews = Review::approved()->count();
        $negativeReviews = Review::negative()->count();
        $averageRating = round(Review::avg('overall_rating') ?? 0, 1);
        $specialists = Specialist::select('id', 'name')->orderBy('name')->get();

        return view('admin.reviews.index', compact(
            'reviews',
            'totalReviews',
            'approvedReviews',
            'negativeReviews',
            'averageRating',
            'specialists'
        ));
    }

    public function show(Review $review)
    {
        $review->load(['user', 'specialist', 'service', 'booking']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review)
    {
        try {
            $review->update(['is_approved' => true]);

            Log::info('Review approved by admin', [
                'review_id' => $review->id,
                'admin_id' => auth()->id()
            ]);

            return back()->with('success', '✅ نظر تایید شد.');

        } catch (\Exception $e) {
            Log::error('خطا در تایید نظر', [
                'review_id' => $review->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در تایید نظر.');
        }
    }

    public function reject(Review $review)
    {
        try {
            $review->update(['is_approved' => false]);

            Log::info('Review rejected by admin', [
                'review_id' => $review->id,
                'admin_id' => auth()->id()
            ]);

            return back()->with('success', 'نظر رد شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در رد نظر.');
        }
    }

    public function toggleFeatured(Review $review)
    {
        try {
            $review->update(['is_featured' => !$review->is_featured]);

            $message = $review->is_featured
                ? '⭐ نظر به عنوان ویژه علامت‌گذاری شد.'
                : 'نظر از حالت ویژه خارج شد.';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در تغییر وضعیت نظر.');
        }
    }

    public function destroy(Review $review)
    {
        try {
            $review->delete();

            Log::warning('Review soft deleted by admin', [
                'review_id' => $review->id,
                'admin_id' => auth()->id()
            ]);

            return back()->with('success', '🗑️ نظر حذف شد.');

        } catch (\Exception $e) {
            Log::error('خطا در حذف نظر', [
                'review_id' => $review->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در حذف نظر.');
        }
    }

    public function restore($id)
    {
        try {
            $review = Review::withTrashed()->findOrFail($id);
            $review->restore();

            return back()->with('success', '♻️ نظر بازگردانی شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بازگردانی نظر.');
        }
    }

    public function forceDelete($id)
    {
        try {
            $review = Review::withTrashed()->findOrFail($id);
            $review->forceDelete();

            Log::warning('Review permanently deleted by admin', [
                'review_id' => $id,
                'admin_id' => auth()->id()
            ]);

            return back()->with('success', '⚠️ نظر به طور دائمی حذف شد.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در حذف دائمی نظر.');
        }
    }

    public function stats()
    {
        $totalReviews = Review::count();
        $averageRating = round(Review::avg('overall_rating') ?? 0, 1);

        $ratingDistribution = Review::select('overall_rating', DB::raw('count(*) as count'))
            ->groupBy('overall_rating')
            ->orderBy('overall_rating', 'desc')
            ->get()
            ->pluck('count', 'overall_rating');

        $topSpecialists = Specialist::select('specialists.*')
            ->join('reviews', 'specialists.id', '=', 'reviews.specialist_id')
            ->where('reviews.is_approved', true)
            ->groupBy('specialists.id')
            ->selectRaw('AVG(reviews.overall_rating) as avg_rating')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->having('review_count', '>=', 3)
            ->orderBy('avg_rating', 'desc')
            ->limit(10)
            ->get();

        $recentNegativeReviews = Review::with(['user', 'specialist', 'service'])
            ->negative()
            ->latest('reviewed_at')
            ->limit(5)
            ->get();

        $monthlyStats = Review::select(
            DB::raw('DATE_FORMAT(reviewed_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw('AVG(overall_rating) as avg_rating')
        )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return view('admin.reviews.stats', compact(
            'totalReviews',
            'averageRating',
            'ratingDistribution',
            'topSpecialists',
            'recentNegativeReviews',
            'monthlyStats'
        ));
    }

    public function trashed()
    {
        $reviews = Review::onlyTrashed()
            ->with(['user', 'specialist', 'service'])
            ->latest('deleted_at')
            ->paginate(15);

        return view('admin.reviews.trashed', compact('reviews'));
    }
}
