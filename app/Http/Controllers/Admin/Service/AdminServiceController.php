<?php

namespace App\Http\Controllers\Admin\Service;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminServiceController extends Controller
{
    public function __construct(protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository) {}

    public function index(): View
    {
        $services = $this->beautyServiceRepository->paginateWithCategory(10);

        return view('admin.services.index', compact('services'));
    }

    public function create(): View
    {
        $categoryService = app(CategoryService::class);
        $categories = $categoryService->getCategorySelectOptions();

        return view('admin.services.create', compact('categories'));
    }

    public function edit(BeautyService $service): View
    {
        $this->ensureSalonOwnership($service->salon_id);

        $categoryService = app(CategoryService::class);
        $categories = $categoryService->getCategorySelectOptions();

        return view('admin.services.edit', compact('service', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store(\App\Support\SalonStorage::forCurrentSalon('services'), 'public');
        }

        $this->beautyServiceRepository->create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'خدمت جدید با موفقیت ایجاد شد.');
    }

    public function update(Request $request, BeautyService $service): RedirectResponse
    {
        $this->ensureSalonOwnership($service->salon_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $validated['image'] = $request->file('image')->store(\App\Support\SalonStorage::forCurrentSalon('services'), 'public');
        }

        $this->beautyServiceRepository->update($service, $validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'خدمت با موفقیت بروزرسانی شد.');
    }

    public function destroy(BeautyService $service): RedirectResponse
    {
        $this->ensureSalonOwnership($service->salon_id);

        try {
            $this->beautyServiceRepository->delete($service);

            return redirect()->route('admin.services.index')
                ->with('success', 'خدمت با موفقیت حذف شد.');

        } catch (\Exception $e) {
            return redirect()->route('admin.services.index')
                ->with('error', $e->getMessage());
        }
    }
}
