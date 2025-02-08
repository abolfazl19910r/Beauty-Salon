<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use Illuminate\Http\Request;

class AdminSpecialistController extends Controller
{
    public function index()
    {
        $specialists = Specialist::latest()->paginate(10);
        return view('admin.specialists.index', compact('specialists'));
    }

    public function create()
    {
        return view('admin.specialists.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:specialists',
            'email' => 'required|email|unique:specialists',
        ]);

        Specialist::create($validated);

        return redirect()->route('admin.specialists.index')
            ->with('success', 'متخصص جدید با موفقیت ایجاد شد.');
    }

    public function edit(Specialist $specialist)
    {
        return view('admin.specialists.edit', compact('specialist'));
    }

    public function update(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:specialists,phone,' . $specialist->id,
            'email' => 'required|email|unique:specialists,email,' . $specialist->id,
        ]);

        $specialist->update($validated);

        return redirect()->route('admin.specialists.index')
            ->with('success', 'اطلاعات متخصص با موفقیت بروزرسانی شد.');
    }

    public function destroy(Specialist $specialist)
    {
        $specialist->delete();
        return redirect()->route('admin.specialists.index')
            ->with('success', 'متخصص با موفقیت حذف شد.');
    }
}
