<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function findByReference(string $referenceId): ?Payment
    {
        return $this->model->where('reference_id', $referenceId)->first();
    }

    public function findByReferenceWithBooking(string $referenceId): ?Payment
    {
        return $this->model->where('reference_id', $referenceId)->with('booking')->first();
    }

    public function findByReferenceWithBookingOrFail(string $referenceId): Payment
    {
        return $this->model->where('reference_id', $referenceId)->with('booking')->firstOrFail();
    }
}
