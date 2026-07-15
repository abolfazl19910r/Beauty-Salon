<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\Category\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\Blog\Category\UpdateBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Services\Admin\Blog\BlogCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AdminBlogCategoryController extends Controller
{
    public function __construct(private readonly BlogCategoryService $blogCategoryService)
    {
    }

    public function index(): View
    {
        return view('admin.blog.categories.index', [
            'categories' => $this->blogCategoryService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.categories.create');
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        try {
            $this->blogCategoryService->store($request->validated());

            return redirect()->route('admin.blog.categories.index')->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
        } catch (Throwable $e) {
            Log::error('خطا در ایجاد دسته‌بندی وبلاگ', ['message' => $e->getMessage()]);

            return back()->withInput()->with('error', 'خطا در ایجاد دسته‌بندی.');
        }
    }

    public function edit(BlogCategory $category): View
    {
        return view('admin.blog.categories.edit', compact('category'));
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $category): RedirectResponse
    {
        try {
            $this->blogCategoryService->update($category, $request->validated());

            return redirect()->route('admin.blog.categories.index')->with('success', 'دسته‌بندی با موفقیت به‌روزرسانی شد.');
        } catch (Throwable $e) {
            Log::error('خطا در به‌روزرسانی دسته‌بندی وبلاگ', ['category_id' => $category->id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('error', 'خطا در به‌روزرسانی دسته‌بندی.');
        }
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        try {
            $this->blogCategoryService->destroy($category);

            return redirect()->route('admin.blog.categories.index')->with('success', 'دسته‌بندی با موفقیت حذف شد.');
        } catch (Throwable $e) {
            return redirect()->route('admin.blog.categories.index')->with('error', $e->getMessage());
        }
    }
}
