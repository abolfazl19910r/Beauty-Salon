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
        try {
            $categories = BlogCategory::all();

            $defaultPublishedAt = Jalalian::now()->format('Y/m/d H:i');

            return view('admin.blog.create', compact('categories', 'defaultPublishedAt'));
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بارگذاری صفحه ایجاد مقاله');
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Auth::check()) {
                throw new \Exception('کاربر احراز هویت نشده است');
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'category_id' => 'required|exists:blog_categories,id',
                'image' => 'nullable|image|max:2048',
                'is_published' => 'nullable',
                'published_at_jalali' => [
                    'nullable',
                    'string',
                    'regex:/^\d{4}\/\d{2}\/\d{2}\s\d{2}:\d{2}$/',
                    function ($attribute, $value, $fail) {
                        try {
                            Jalalian::fromFormat('Y/m/d H:i', $value);
                        } catch (\Exception $e) {
                            $fail('فرمت تاریخ نامعتبر است. فرمت صحیح: YYYY/MM/DD HH:MM');
                        }
                    }
                ]
            ]);

            $validated['is_published'] = $this->convertToBoolean($request->input('is_published'));

            if (!empty($validated['published_at_jalali'])) {
                try {
                    $jalaliDate = Jalalian::fromFormat('Y/m/d H:i', $validated['published_at_jalali']);
                    $validated['published_at'] = $jalaliDate->toCarbon();
                } catch (\Exception $e) {
                    return back()->withInput()->with('error', 'فرمت تاریخ نامعتبر است');
                }
            } elseif ($validated['is_published']) {
                $validated['published_at'] = now();
            }

            if ($request->hasFile('image')) {
                try {
                    $imagePath = $request->file('image')->store('blog', 'public');
                    $validated['image'] = $imagePath;
                } catch (\Exception $imageException) {
                    return back()->withInput()->with('error', 'خطا در آپلود تصویر');
                }
            }

            $validated['author_id'] = Auth::id();
            $validated['slug'] = Str::slug($validated['title']);

            if (isset($validated['excerpt']) && strlen($validated['excerpt']) > 500) {
                $validated['excerpt'] = Str::limit($validated['excerpt'], 500);
            }

            $category = BlogCategory::findOrFail($validated['category_id']);

            $post = BlogPost::create($validated);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'مقاله با موفقیت ایجاد شد.',
                    'data' => $post->load('category')
                ], 201);
            }

            return redirect()->route('admin.blog.index')
                ->with('success', 'مقاله با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در ایجاد مقاله: ' . $e->getMessage(),
                    'error_details' => config('app.debug') ? $e->getTraceAsString() : null
                ], 500);
            }

            return back()->with('error', 'خطا در ایجاد مقاله: ' . $e->getMessage());
        }
    }

    private function convertToBoolean($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'yes', 'on']);
        }

        if (is_numeric($value)) {
            return $value == 1;
        }

        return false;
    }

    public function show(BlogPost $post)
    {
        try {
            return view('admin.blog.show', compact('post'));
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در نمایش مقاله');
        }
    }

    public function edit(BlogPost $post)
    {
        try {
            $categories = BlogCategory::all();

            return view('admin.blog.edit', compact('post', 'categories'));
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بارگذاری صفحه ویرایش مقاله');
        }
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
