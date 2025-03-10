<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminBlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('order')
            ->paginate(10);

        return view('admin.blog.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.blog.categories.create');
    }

    public function store(Request $request)
    {
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

        return redirect()->route('admin.blog.categories.index')
            ->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
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
