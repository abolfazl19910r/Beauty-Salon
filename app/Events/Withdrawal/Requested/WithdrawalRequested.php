<?php

namespace App\Events\Withdrawal\Requested;

use App\Models\WithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WithdrawalRequest $withdrawalRequest,
    ) {
    }
}
