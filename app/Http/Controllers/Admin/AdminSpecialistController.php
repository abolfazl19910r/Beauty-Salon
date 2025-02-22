<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSpecialistController extends Controller
{
    public function index()
    {
        $specialists = Specialist::whereNull('deleted_at')
        ->latest()
            ->paginate(10);
        return view('admin.specialists.index', compact('specialists'));
    }

    public function create()
    {
        $services = ServiceCategory::with('services')->get();
        return view('admin.specialists.create', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:specialists',
            'email' => 'required|email|unique:specialists',
            'services' => ['required', 'array'],
            'services.*' => ['exists:beauty_services,id']
        ]);

        $services = $validated['services'];
        unset($validated['services']);

        $specialist = Specialist::create($validated);

        $specialist->services()->attach($services);

        return redirect()->route('admin.specialists.index')
            ->with('success', 'متخصص جدید با موفقیت ایجاد شد.');
    }

    public function edit($id)
    {
        $specialist = Specialist::find($id);
        if (!$specialist) {
            abort(404);
        }
        $services = ServiceCategory::with('services')->get();
        $selectedServices = $specialist->services->pluck('id')->toArray();
        return view('admin.specialists.edit', compact('specialist', 'services', 'selectedServices'));
    }

    public function update(Request $request, $id)
    {
        $specialist = Specialist::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:specialists,phone,' . $specialist->id,
            'email' => 'required|email|unique:specialists,email,' . $specialist->id,
            'services' => ['required', 'array'],
            'services.*' => ['exists:beauty_services,id']
        ]);

        $services = $validated['services'];
        unset($validated['services']);

        $specialist->update($validated);

        $specialist->services()->sync($services);

        return redirect()->route('admin.specialists.index')
            ->with('success', 'اطلاعات متخصص با موفقیت بروزرسانی شد.');
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $specialist = Specialist::findOrFail($id);
            Log::info('Attempting to delete specialist ID: ' . $id);

            $specialist->services()->detach();
            $result = $specialist->delete();

            DB::commit();

            return redirect()->route('admin.specialists.index')
                ->with('success', 'متخصص با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in destroy method: ' . $e->getMessage());

            return redirect()->route('admin.specialists.index')
                ->with('error', 'خطا در حذف متخصص');
        }
    }
}
