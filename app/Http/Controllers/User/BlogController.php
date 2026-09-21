<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use App\Repositories\Contracts\BlogPostRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogPostRepositoryInterface $blogPostRepository,
        private readonly BlogCategoryRepositoryInterface $blogCategoryRepository,
    ) {}

    public function index(Request $request): View
    {
        $posts = $this->blogPostRepository->paginatePublished($request->category, 9);
        $categories = $this->blogCategoryRepository->getWithPostsCount();

        return view('blog.index', compact('posts', 'categories'));
    }

    public function show(BlogPost $post): View
    {
        $this->ensureSalonOwnership($post->salon_id);

        if (! $post->is_published || $post->published_at > now()) {
            abort(404);
        }

        $post->load('category', 'author');
        $post->increment('views');

        $relatedPosts = $this->blogPostRepository->getRelatedPublished($post->category_id, $post->id, 3);

        return view('blog.show', compact('post', 'relatedPosts'));
    }
}
