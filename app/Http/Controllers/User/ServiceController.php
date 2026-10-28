<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        protected CurrentSalon $currentSalon,
        protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
        protected readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function index(): View
    {
        $services = $this->beautyServiceRepository->paginateForIndex(request('category'), 12);
        $categories = $this->categoryRepository->all();

        return view('services.index', compact('services', 'categories'));
    }

    public function show(BeautyService $service): View
    {
        $this->ensureSalonOwnership($service->salon_id);

        $specialists = $service->specialists()
            ->with(['schedules' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')])
            ->get();

        $relatedServices = $this->beautyServiceRepository->getRelated($service->category_id, $service->id, 3);

        return view('services.show', compact('service', 'specialists', 'relatedServices'));
    }

    public function list(): JsonResponse
    {
        $salonId = $this->currentSalon->id();

        $services = Cache::remember("beauty_services:{$salonId}", now()->addMinutes(30), fn () => $this->beautyServiceRepository->all());

        // بسته‌ی 5ه: پیش‌پرداخت واقعی همین مشتری (بدون پیش‌پرداخت = ۰)
        $policy = app(\App\Services\Booking\BookingPaymentPolicy::class);
        $customer = auth()->user();
        $services = $services->map(function (BeautyService $service) use ($policy, $customer) {
            $service->prepayment_amount = $policy->termsFor($customer, (float) $service->price)->prepaymentAmount;

            return $service;
        });

        return response()->json($services);
    }

    public function specialists(BeautyService $beautyService): JsonResponse
    {
        return response()->json($beautyService->specialists);
    }
}
