<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'points' => $this->points,
            'type' => $this->type,
            'description' => $this->description,
            'booking' => $this->when($this->booking, function() {
                return [
                    'id' => $this->booking->id,
                    'date' => $this->booking->booking_time,
                    'service' => $this->booking->service->name,
                    'specialist' => $this->booking->specialist->name
                ];
            }),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
