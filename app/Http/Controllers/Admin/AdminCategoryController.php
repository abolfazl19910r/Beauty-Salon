<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $query = Category::with('parent');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->has('parent_id') && !empty($request->parent_id)) {
            $query->where('parent_id', $request->parent_id);
        }

        $categories = $query->orderBy('order', 'asc')->paginate(10);
        $parentCategories = Category::parents()->get(['id', 'name']);

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    public function create()
    {
        $parentCategories = Category::parents()->get(['id', 'name']);
        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'image' => 'nullable|image|max:2048'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $validated['image'] = $path;
        }

        try {
            $category = $this->categoryService->create($validated);

            return redirect()->route('admin.categories.index')
                ->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'خطا در ایجاد دسته‌بندی. لطفا مجددا تلاش کنید.');
        }
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);

        $category->load(['parent', 'children']);

        $childrenCount = $category->children->count();
        $servicesCount = $category->services->count();

        return view('admin.categories.show', compact('category', 'childrenCount', 'servicesCount'));
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);

        $categories = Category::where('id', '!=', $category->id)
            ->get(['id', 'name']);

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean'
        ]);

        if ($category->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($request->has('remove_image') && $request->remove_image) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
                $validated['image'] = null;
            }
        }
        elseif ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $path = $request->file('image')->store('categories', 'public');
            $validated['image'] = $path;
        }

        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return redirect()->back()->withInput()
                ->with('error', 'یک دسته‌بندی نمی‌تواند والد خودش باشد.');
        }

        try {
            $this->categoryService->update($category, $validated);

            return redirect()->route('admin.categories.index')
                ->with('success', 'دسته‌بندی با موفقیت به‌روزرسانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'خطا در به‌روزرسانی دسته‌بندی. لطفا مجددا تلاش کنید.');
        }
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);

        try {
            $this->categoryService->toggleStatus($category);
            $status = $category->fresh()->is_active ? 'فعال' : 'غیرفعال';

            return redirect()->route('admin.categories.index')
                ->with('success', "دسته‌بندی با موفقیت {$status} شد.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در تغییر وضعیت دسته‌بندی. لطفا مجددا تلاش کنید.');
        }
    }

    public function destroy(Category $category)
    {
        try {
            if ($category->children()->count() > 0 || $category->services()->count() > 0) {
                return redirect()->route('admin.categories.index')
                    ->with('error', 'این دسته‌بندی دارای زیردسته یا سرویس است و قابل حذف نیست.');
            }

            $this->categoryService->delete($category);

            return redirect()->route('admin.categories.index')
                ->with('success', 'دسته‌بندی با موفقیت حذف شد.');
        } catch (\Exception $e) {
            Log::error('خطا در حذف دسته‌بندی', [
                'error' => $e->getMessage(),
                'category_id' => $category->id
            ]);

            return redirect()->back()
                ->with('error', 'خطا در حذف دسته‌بندی. لطفا مجددا تلاش کنید.');
        }
    }
}
