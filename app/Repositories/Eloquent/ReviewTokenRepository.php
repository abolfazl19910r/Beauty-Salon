<?php

namespace App\Repositories\Eloquent;

use App\Models\ReviewToken;
use App\Repositories\Contracts\ReviewTokenRepositoryInterface;

class ReviewTokenRepository extends BaseRepository implements ReviewTokenRepositoryInterface
{
    public function __construct(ReviewToken $model)
    {
        parent::__construct($model);
    }

    public function findByToken(string $token): ?ReviewToken
    {
        return $this->model->where('token', $token)->first();
    }

    public function findValidToken(string $token): ?ReviewToken
    {
        return $this->model->where('token', $token)
            ->valid()
            ->first();
    }
}
