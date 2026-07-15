<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Admin\Blog\BlogPostService;
use Illuminate\View\View;

class AdminBlogController extends Controller
{
    public function __construct(private readonly BlogPostService $blogPostService)
    {
    }

    public function index(): View
    {
        return view('admin.blog.index', $this->blogPostService->getIndexData());
    }

    public function show(BlogPost $post): View
    {
        $post->load('category', 'author');

        return view('admin.blog.show', compact('post'));
    }
}
