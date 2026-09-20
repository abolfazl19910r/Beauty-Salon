<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    public function findByReference(string $referenceId): ?Payment;

    public function findByReferenceWithBooking(string $referenceId): ?Payment;

    public function findByReferenceWithBookingOrFail(string $referenceId): Payment;
}
