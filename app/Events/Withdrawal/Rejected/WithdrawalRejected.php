<?php

namespace App\Events\Withdrawal\Rejected;

use App\Models\WithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WithdrawalRequest $withdrawalRequest,
        public string $reason,
    ) {}
}
