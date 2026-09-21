<?php

namespace App\Repositories\Contracts;

use App\Models\ReviewToken;

interface ReviewTokenRepositoryInterface extends RepositoryInterface
{
    public function findByToken(string $token): ?ReviewToken;

    public function findValidToken(string $token): ?ReviewToken;
}
