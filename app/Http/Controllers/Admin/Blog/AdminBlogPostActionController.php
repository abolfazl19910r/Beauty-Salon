<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\Post\StoreAdminBlogPostRequest;
use App\Http\Requests\Admin\Blog\Post\UpdateAdminBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\Admin\Blog\BlogPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AdminBlogPostActionController extends Controller
{
    public function __construct(private readonly BlogPostService $blogPostService) {}

    public function create(): View
    {
        return view('admin.blog.create', [
            'categories' => BlogCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAdminBlogPostRequest $request): RedirectResponse
    {
        try {
            $this->blogPostService->store($request->validated(), $request->file('image'));

            return redirect()->route('admin.blog.index')->with('success', 'مقاله با موفقیت ایجاد شد.');
        } catch (Throwable $e) {
            Log::error('خطا در ایجاد مقاله وبلاگ', ['message' => $e->getMessage()]);

            return back()->withInput()->with('error', 'خطا در ایجاد مقاله. لطفاً دوباره تلاش کنید.');
        }
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.edit', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAdminBlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        try {
            $this->blogPostService->update($post, $request->validated(), $request->file('image'));

            return redirect()->route('admin.blog.index')->with('success', 'مقاله با موفقیت به‌روزرسانی شد.');
        } catch (Throwable $e) {
            Log::error('خطا در به‌روزرسانی مقاله وبلاگ', ['post_id' => $post->id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('error', 'خطا در به‌روزرسانی مقاله. لطفاً دوباره تلاش کنید.');
        }
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        try {
            $this->blogPostService->destroy($post);

            return redirect()->route('admin.blog.index')->with('success', 'مقاله با موفقیت حذف شد.');
        } catch (Throwable $e) {
            Log::error('خطا در حذف مقاله وبلاگ', ['post_id' => $post->id, 'message' => $e->getMessage()]);

            return back()->with('error', 'خطا در حذف مقاله.');
        }
    }

    public function togglePublish(BlogPost $post): RedirectResponse
    {
        try {
            $post = $this->blogPostService->togglePublish($post);
            $status = $post->is_published ? 'منتشر' : 'پیش‌نویس';

            return back()->with('success', "مقاله با موفقیت به حالت {$status} تغییر یافت.");
        } catch (Throwable $e) {
            Log::error('خطا در تغییر وضعیت مقاله وبلاگ', ['post_id' => $post->id, 'message' => $e->getMessage()]);

            return back()->with('error', 'خطا در تغییر وضعیت مقاله.');
        }
    }
}
