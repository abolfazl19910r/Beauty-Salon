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

    public function top(): JsonResponse
    {
        return response()->json($this->announcementRepository->getTopActive());
    }

    public function index(): JsonResponse
    {
        return response()->json($this->announcementRepository->paginateActive(10));
    }

    /**
     * @param  int  $id
     */
    public function show($id): JsonResponse
    {
        return response()->json($this->announcementRepository->findActiveOrFail($id));
    }
}
