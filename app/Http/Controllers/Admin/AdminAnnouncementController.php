<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AdminAnnouncementController extends Controller
{
    public function index()
    {
        $totalAnnouncements = Announcement::count();

        $activeAnnouncements = Announcement::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $pendingAnnouncements = Announcement::where('is_active', true)
            ->where('published_at', '>', now())
            ->count();

        $expiredAnnouncements = Announcement::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        return view('admin.announcements.index', compact(
            'totalAnnouncements',
            'activeAnnouncements',
            'pendingAnnouncements',
            'expiredAnnouncements'
        ));
    }

    public function stats()
    {
        $stats = [
            'total' => Announcement::count(),
            'active' => Announcement::where('is_active', true)
                ->where('published_at', '<=', now())
                ->where(function($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count(),
            'pending' => Announcement::where('is_active', true)
                ->where('published_at', '>', now())
                ->count(),
            'expired' => Announcement::whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count()
        ];

        return response()->json($stats);
    }

    public function list()
    {
        $announcements = Announcement::orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json($announcements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
            'type' => 'required|in:general,maintenance,promotion',
            'priority' => 'required|integer|min:0',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at'
        ]);

        $announcement = Announcement::create($validated);

        return response()->json($announcement, 201);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'type' => 'sometimes|in:general,maintenance,promotion',
            'priority' => 'sometimes|integer|min:0',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at'
        ]);

        $announcement->update($validated);

        return response()->json($announcement);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json([
            'message' => 'اطلاعیه با موفقیت حذف شد'
        ]);
    }
}
