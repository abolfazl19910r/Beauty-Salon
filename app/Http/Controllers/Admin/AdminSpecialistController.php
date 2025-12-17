<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Services\CategoryService;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSpecialistController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        $specialists = Specialist::whereNull('deleted_at')
            ->latest()
            ->paginate(10);
        return view('admin.specialists.index', compact('specialists'));
    }

    public function show(Specialist $specialist)
    {
        return view('admin.specialists.show', compact('specialist'));
    }

    public function create()
    {
        $services = Category::with('services')->get();

        return view('admin.specialists.create', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:specialists,phone',
            'email' => 'required|email|unique:specialists,email',
            'services' => ['required', 'array'],
            'services.*' => ['exists:beauty_services,id'],
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $services = $validated['services'];
                unset($validated['services']);

                $validated['user_id'] = auth()->id();

                $specialist = Specialist::create($validated);
                $specialist->services()->attach($services);

                return redirect()
                    ->route('admin.specialists.index')
                    ->with('success', 'متخصص جدید با موفقیت ایجاد شد.');
            });
        } catch (\Exception $e) {
            Log::error('Error storing specialist: ' . $e->getMessage());
            return back()->with('error', 'خطایی در ثبت اطلاعات رخ داد.')->withInput();
        }
    }

    public function edit(Specialist $specialist)
    {
        $services = Category::with('services')->get();
        $selectedServices = $specialist->services->pluck('id')->toArray();
        return view('admin.specialists.edit', compact('specialist', 'services', 'selectedServices'));
    }

    public function update(Request $request, Specialist $specialist)
    {
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

    public function destroy(Specialist $specialist)
    {
        try {
            DB::beginTransaction();

            $specialist->services()->detach();
            $specialist->delete();

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
