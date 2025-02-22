<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminServiceController extends Controller
{
    public function index()
    {
        $services = BeautyService::with('category')->latest()->paginate(10);
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        $categories = ServiceCategory::all();
        return view('admin.services.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:service_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        BeautyService::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'خدمت جدید با موفقیت ایجاد شد.');
    }

    public function edit($id)
    {
        $service = BeautyService::findOrFail($id);
        $categories = ServiceCategory::all();
        return view('admin.services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $service = BeautyService::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:service_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $validated['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'خدمت با موفقیت بروزرسانی شد.');
    }

    public function destroy($id)
    {
        try {
            $service = BeautyService::findOrFail($id);

            Log::info('Attempting to delete service:', [
                'id' => $id,
                'name' => $service->name,
                'bookings_count' => $service->bookings()->count(),
                'specialists_count' => $service->specialists()->count()
            ]);

            if ($service->delete()) {
                return redirect()->route('admin.services.index')
                    ->with('success', 'خدمت با موفقیت حذف شد.');
            }

            return redirect()->route('admin.services.index')
                ->with('error', 'خطا در حذف خدمت.');

        } catch (\Exception $e) {
            Log::error('Error deleting service:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.services.index')
                ->with('error', 'خطا در حذف خدمت: ' . $e->getMessage());
        }
    }
}
