<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class AdminBlogController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->expectsJson()) {
                $posts = BlogPost::with('category')
                    ->latest()
                    ->get()
                    ->map(function($post) {
                        $post->image_url = $post->image ? asset('storage/' . $post->image) : null;
                        return $post;
                    });

                return response()->json([
                    'success' => true,
                    'data' => $posts,
                    'total_views' => BlogPost::sum('views'),
                    'post_count' => BlogPost::count(),
                    'category_count' => BlogCategory::count(),
                    'message' => 'مقالات با موفقیت دریافت شدند'
                ]);
            }

            $posts = BlogPost::with('category')->latest()->get();
            $categories = BlogCategory::all();
            $stats = [
                'total_views' => BlogPost::sum('views'),
                'post_count' => BlogPost::count(),
                'category_count' => BlogCategory::count()
            ];

            return view('admin.blog.index', compact('posts', 'categories', 'stats'));
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در دریافت مقالات');
        }
    }

    public function create()
    {
        $categories = BlogCategory::all();
        return view('admin.blog.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|exists:blog_categories,id',
            'image' => 'nullable|image|max:2048',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('blog', 'public');
        }

        $validated['author_id'] = Auth::id();

        if (isset($validated['is_published']) && $validated['is_published'] && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post = BlogPost::create($validated);

        return redirect()->route('admin.blog.index')
            ->with('success', 'مقاله با موفقیت ایجاد شد.');
    }

    public function show(BlogPost $post)
    {
        return view('admin.blog.show', compact('post'));
    }

    public function edit(BlogPost $post)
    {
        $categories = BlogCategory::all();
        return view('admin.blog.edit', compact('post', 'categories'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|exists:blog_categories,id',
            'image' => 'nullable|image|max:2048',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date'
        ]);

        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $validated['image'] = $request->file('image')->store('blog', 'public');
        }

        if (isset($validated['is_published']) && $validated['is_published'] && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        return redirect()->route('admin.blog.index')
            ->with('success', 'مقاله با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(BlogPost $post)
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();

        return redirect()->route('admin.blog.index')
            ->with('success', 'مقاله با موفقیت حذف شد.');
    }

    public function togglePublish(BlogPost $post)
    {
        $post->is_published = !$post->is_published;

        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        $status = $post->is_published ? 'منتشر' : 'پیش‌نویس';

        return redirect()->route('admin.blog.index')
            ->with('success', "مقاله با موفقیت به حالت {$status} تغییر یافت.");
    }
}
