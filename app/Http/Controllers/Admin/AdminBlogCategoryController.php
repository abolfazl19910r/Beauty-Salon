<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AdminBlogCategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $categories = BlogCategory::withCount('posts')
                ->orderBy('order')
                ->paginate(10);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $categories->items(),
                    'meta' => [
                        'total' => $categories->total(),
                        'per_page' => $categories->perPage(),
                        'current_page' => $categories->currentPage()
                    ],
                    'message' => 'دسته‌بندی‌ها با موفقیت دریافت شدند'
                ]);
            }

            return view('admin.blog.categories.index', compact('categories'));

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در دریافت دسته‌بندی‌ها: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'خطا در دریافت دسته‌بندی‌ها');
        }
    }

    public function create(Request $request)
    {
        try {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'صفحه ایجاد دسته‌بندی آماده است'
                ]);
            }

            return view('admin.blog.categories.create');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بارگذاری صفحه ایجاد دسته‌بندی');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:blog_categories',
                'description' => 'nullable|string',
                'order' => 'nullable|integer'
            ]);

            if (empty($validated['order'])) {
                $validated['order'] = BlogCategory::max('order') + 1;
            }

            $validated['slug'] = Str::slug($validated['name']);

            $category = BlogCategory::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $category,
                    'message' => 'دسته‌بندی با موفقیت ایجاد شد.'
                ], 201);
            }

            return redirect()->route('admin.blog.categories.index')
                ->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در ایجاد دسته‌بندی: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'خطا در ایجاد دسته‌بندی');
        }
    }

    public function edit(BlogCategory $category)
    {
        return view('admin.blog.categories.edit', compact('category'));
    }

    public function update(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:blog_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'order' => 'nullable|integer'
        ]);

        if ($category->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return redirect()->route('admin.blog.categories.index')
            ->with('success', 'دسته‌بندی با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(BlogCategory $category)
    {
        if ($category->posts()->count() > 0) {
            return redirect()->route('admin.blog.categories.index')
                ->with('error', 'این دسته‌بندی دارای مقاله است و نمی‌توان آن را حذف کرد.');
        }

        $category->delete();

        return redirect()->route('admin.blog.categories.index')
            ->with('success', 'دسته‌بندی با موفقیت حذف شد.');
    }
}
