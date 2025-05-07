<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\SpecialistLeave;
use Illuminate\Http\Request;

class AdminSpecialistLeaveController extends Controller
{
    public function index(Specialist $specialist)
    {
        $leaves = $specialist->leaves()->latest()->paginate(10);
        return view('admin.specialists.leaves.index', compact('specialist', 'leaves'));
    }

    public function store(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255'
        ]);

        $specialist->leaves()->create($validated);

        return redirect()->route('admin.specialists.leaves.index', ['specialist' => $specialist->id])
            ->with('success', 'مرخصی با موفقیت ثبت شد.');
    }

    public function update(Request $request, Specialist $specialist, SpecialistLeave $leave)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $leave->update($validated);

        return redirect()->route('admin.specialists.leaves.index', $specialist)
            ->with('success', 'وضعیت مرخصی با موفقیت بروزرسانی شد.');
    }
}
