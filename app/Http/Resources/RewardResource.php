<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userPoints = auth()->user()->getCurrentPoints();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'required_points' => $this->required_points,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'available' => $this->isAvailableForUser(auth()->user()),
            'is_achievable' => $userPoints >= $this->required_points,
            'points_needed' => max(0, $this->required_points - $userPoints),
            'remaining_uses' => $this->when($this->max_uses !== null,
                fn () => $this->max_uses - $this->used_count
            ),
        ];
    }
}
