<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function active(): JsonResponse
    {
        return response()->json($this->announcementRepository->getActive());
    }
}
