<?php
// app/Observers/BookingObserver.php

namespace App\Observers;

use App\Models\Booking;
use App\Services\ReportCacheService;

class BookingObserver
{
    protected ReportCacheService $cacheService;

    public function __construct(ReportCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function created(Booking $booking): void
    {
        $this->cacheService->flush();
    }

    public function updated(Booking $booking): void
    {
        $this->cacheService->flush();
    }

    public function deleted(Booking $booking): void
    {
        $this->cacheService->flush();
    }
}
