<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'stats' => [
                'todayBookings' => $this->todayBookings,
                'totalRevenue' => $this->totalRevenue,
                'totalUsers' => $this->totalUsers,
                'totalSpecialists' => $this->totalSpecialists,
                'revenueChange' => $this->revenueChange,
                'usersChange' => $this->usersChange,
            ],
            'dailyRevenue' => $this->daily_revenue->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => $item->total,
                    'bookings_count' => $item->bookings_count,
                ];
            }),
            'popularServices' => $this->popular_services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'bookings_count' => $service->bookings_count,
                    'revenue' => $service->revenue,
                    'trend' => $service->trend,
                ];
            }),
            'activeSpecialists' => $this->active_specialists->map(function ($specialist) {
                return [
                    'id' => $specialist->id,
                    'name' => $specialist->name,
                    'image' => $specialist->image_url,
                    'expertise' => $specialist->expertise,
                    'completion_rate' => $specialist->completion_rate,
                    'rating' => $specialist->average_rating,
                    'performance_score' => $specialist->performance_score,
                    'successful_bookings' => $specialist->successful_bookings_count,
                    'revenue' => $specialist->total_revenue,
                    'top_performer' => $specialist->is_top_performer,
                ];
            }),
        ];
    }
}
