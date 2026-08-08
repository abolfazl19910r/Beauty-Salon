<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = BlogPost::with('category')
            ->when($request->category, function ($query, $category) {
                return $query->where('category_id', $category);
            })
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogCategory::withCount('posts')->orderBy('order')->orderBy('name')->get();

        return view('blog.index', compact('posts', 'categories'));
    }

    public function show(BlogPost $post): View
    {
        if (! $post->is_published || $post->published_at > now()) {
            abort(404);
        }

        $post->load('category', 'author');
        $post->increment('views');

        $relatedPosts = BlogPost::with('category')
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('post', 'relatedPosts'));
    }
}
