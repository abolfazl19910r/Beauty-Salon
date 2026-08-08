<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function active(): JsonResponse
    {
        $announcements = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json($announcements);
    }

    public function top(): JsonResponse
    {
        $announcement = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'desc')
            ->first();

        return response()->json($announcement);
    }

    public function index(): JsonResponse
    {
        $announcements = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return response()->json($announcements);
    }

    /**
     * @param  int  $id
     */
    public function show($id): JsonResponse
    {
        $announcement = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->findOrFail($id);

        return response()->json($announcement);
    }
}
