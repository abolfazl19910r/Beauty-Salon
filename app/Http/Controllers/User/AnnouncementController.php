<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function active()
    {
        $announcements = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json($announcements);
    }

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function top()
    {
        $announcement = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'desc')
            ->first();

        return response()->json($announcement);
    }

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $announcements = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return response()->json($announcements);
    }

    /**
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $announcement = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->findOrFail($id);

        return response()->json($announcement);
    }
}
