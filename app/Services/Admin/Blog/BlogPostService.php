<?php

namespace App\Services\Admin\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Traits\HasJalaliDates;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostService
{
    use HasJalaliDates;

    public function getIndexData(): array
    {
        return [
            'posts' => BlogPost::with('category')->latest()->paginate(15),
            'stats' => [
                'total_views' => (int) BlogPost::sum('views'),
                'post_count' => BlogPost::count(),
                'category_count' => BlogCategory::count(),
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
                $attributes['image'] = $image->store('blog', 'public');
            }

            $post = new BlogPost;
            $post->fill($attributes);
            $post->save();

            return $post;
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
                $attributes['image'] = $image->store('blog', 'public');
            }

            $post->update($attributes);

            return $post->fresh();
        });
    }

    public function destroy(BlogPost $post): void
    {
        DB::transaction(function () use ($post) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $post->delete();
        });
    }

    public function togglePublish(BlogPost $post): BlogPost
    {
        return DB::transaction(function () use ($post) {
            $post->is_published = ! $post->is_published;

            if ($post->is_published && ! $post->published_at) {
                $post->published_at = now();
            }

            $post->save();

            return $post;
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

    /**
     * HTML checkboxes are not sent at all in the request when they are not checked;
     * Previously, this key was not explicitly normalized in update(), i.e. removed
     * The "published" tick had no effect when editing, because the is_published key does not work at all
     * $post->update() was not passed. Now always set to true/false explicitly.
     */
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

    /**
     * Same logic for store and update:
     * - If explicit solar date entered, same date (admin conscious override).
     * - If published and has no previous publication date (first time), now.
     * - Otherwise previous date is left untouched (whether published or draft)
     * — Similar behavior to togglePublish which does not clear date when drafting.
     */
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
