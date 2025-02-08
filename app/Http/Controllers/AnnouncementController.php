<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::active()
            ->when(request()->expectsJson(), function ($query) {
                return $query->latest('published_at');
            }, function ($query) {
                return $query->byPriority()->paginate(15);
            });

        return request()->expectsJson()
            ? response()->json($announcements)
            : view('announcements.index', compact('announcements'));
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

        return $request->expectsJson()
            ? response()->json($announcement, 201)
            : redirect()->route('announcements.index')->with('success', 'اطلاعیه با موفقیت ایجاد شد.');
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

        return request()->expectsJson()
            ? response()->json(['message' => 'اعلان با موفقیت حذف شد'])
            : redirect()->route('announcements.index')->with('success', 'اعلان با موفقیت حذف شد');
    }

    public function edit(Announcement $announcement)
    {
        return view('announcements.edit', compact('announcement'));
    }
}
