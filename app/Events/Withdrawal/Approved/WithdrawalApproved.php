<?php

namespace App\Events\Withdrawal\Approved;

use App\Models\WithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WithdrawalRequest $withdrawalRequest,
    ) {
    }
}
