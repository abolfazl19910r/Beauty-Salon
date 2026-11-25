<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⭐ بسته‌ی ۵الف (درخواست ابوالفضل، ۲۰۲۶-۱۱-۲۵): صفحه‌ی عمومی «نظرها»ی سالن — نظرهای تأییدشده‌ی همه‌ی کارشناسان با پاسخ
 * کارشناس، میانگین، نمودار ستاره‌ها و فیلتر کارشناس/ستاره. فقط‌خواندنی: ثبت نظر همچنان فقط از پیوند پیامکی پس از انجام
 * خدمت (reviews.create با توکن). نام مشتری کوتاه (CustomerName::short). همه‌چیز با scope ‏BelongsToSalon.
 */
class SalonReviewController extends Controller
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
        private readonly SpecialistRepositoryInterface $specialists,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'specialist' => $request->integer('specialist') ?: null,
            'rating' => in_array($request->integer('rating'), [1, 2, 3, 4, 5], true) ? $request->integer('rating') : null,
        ];

        return view('reviews.index', [
            'reviews' => $this->reviews->paginateApprovedForSalon($filters, 12),
            'stats' => $this->reviews->getSalonStats(),
            'experts' => $this->specialists->query()->whereHas('reviews', fn ($q) => $q->where('is_approved', true))->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }
}
