<?php

namespace App\Services\Admin\Blog;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use App\Repositories\Contracts\BlogPostRepositoryInterface;
use App\Traits\HasJalaliDates;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostService
{
    use HasJalaliDates;

    public function __construct(
        private readonly BlogPostRepositoryInterface $blogPostRepository,
        private readonly BlogCategoryRepositoryInterface $blogCategoryRepository,
    ) {}

    public function getIndexData(): array
    {
        return [
            'posts' => $this->blogPostRepository->paginateWithCategory(15),
            'stats' => [
                'total_views' => $this->blogPostRepository->sumViews(),
                'post_count' => $this->blogPostRepository->count(),
                'category_count' => $this->blogCategoryRepository->count(),
            ],
        ];
    }

    public function store(array $data, ?UploadedFile $image): BlogPost
    {
        return DB::transaction(function () use ($data, $image) {
            $attributes = $this->prepareAttributes($data);
            $attributes['published_at'] = $this->resolvePublishedAt($data, $attributes['is_published'], null);
            $attributes['author_id'] = auth()->id();
            $attributes['slug'] = Str::slug($data['title']);

            if ($image) {
                $attributes['image'] = $image->store(\App\Support\SalonStorage::forCurrentSalon('blog'), 'public');
            }

            return $this->blogPostRepository->create($attributes);
        });
    }

    public function update(BlogPost $post, array $data, ?UploadedFile $image): BlogPost
    {
        return DB::transaction(function () use ($post, $data, $image) {
            $attributes = $this->prepareAttributes($data);
            $attributes['published_at'] = $this->resolvePublishedAt($data, $attributes['is_published'], $post);

            if ($data['title'] !== $post->title) {
                $attributes['slug'] = Str::slug($data['title']);
            }

            if ($image) {
                if ($post->image) {
                    Storage::disk('public')->delete($post->image);
                }
                $attributes['image'] = $image->store(\App\Support\SalonStorage::forCurrentSalon('blog'), 'public');
            }

            $post = $this->blogPostRepository->update($post, $attributes);

            return $post->fresh();
        });
    }

    public function destroy(BlogPost $post): void
    {
        DB::transaction(function () use ($post) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $this->blogPostRepository->delete($post);
        });
    }

    public function togglePublish(BlogPost $post): BlogPost
    {
        return DB::transaction(function () use ($post) {
            $isPublished = ! $post->is_published;
            $attributes = ['is_published' => $isPublished];

            if ($isPublished && ! $post->published_at) {
                $attributes['published_at'] = now();
            }

            return $this->blogPostRepository->update($post, $attributes);
        });
    }

    private function prepareAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'content' => $data['content'],
            'excerpt' => isset($data['excerpt']) && $data['excerpt'] !== ''
                ? Str::limit($data['excerpt'], 500, '')
                : null,
            'category_id' => $data['category_id'],
            'is_published' => $this->normalizeIsPublished($data['is_published'] ?? null),
        ];
    }

    private function normalizeIsPublished(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function resolvePublishedAt(array $data, bool $isPublished, ?BlogPost $existing): ?Carbon
    {
        if (! empty($data['published_at_jalali'])) {
            return $this->parseJalaliOrFail($data['published_at_jalali'], 'Y/m/d H:i');
        }

        if ($isPublished && ! $existing?->published_at) {
            return now();
        }

        return $existing?->published_at;
    }
}
