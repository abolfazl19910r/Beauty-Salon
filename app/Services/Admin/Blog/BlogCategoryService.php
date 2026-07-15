<?php

namespace App\Services\Admin\Blog;

use App\Models\BlogCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogCategoryService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return BlogCategory::withCount('posts')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function store(array $data): BlogCategory
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = Str::slug($data['name']);
            $data['order'] = $data['order'] ?? ((int) BlogCategory::max('order') + 1);

            return BlogCategory::create($data);
        });
    }

    public function update(BlogCategory $category, array $data): BlogCategory
    {
        return DB::transaction(function () use ($category, $data) {
            if ($category->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return $category->fresh();
        });
    }

    public function destroy(BlogCategory $category): void
    {
        if ($category->posts()->exists()) {
            throw new \RuntimeException('این دسته‌بندی دارای مقاله است و نمی‌توان آن را حذف کرد.');
        }

        $category->delete();
    }
}
