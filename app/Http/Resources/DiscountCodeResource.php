<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => $this->amount,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'remaining_uses' => $this->max_uses ? ($this->max_uses - $this->used_count) : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
