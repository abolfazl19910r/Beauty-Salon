<?php

namespace App\Services\Admin\Blog;

use App\Models\BlogCategory;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogCategoryService
{
    public function __construct(
        private readonly BlogCategoryRepositoryInterface $blogCategoryRepository,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->blogCategoryRepository->paginateWithPostsCount($perPage);
    }

    public function store(array $data): BlogCategory
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = Str::slug($data['name']);
            $data['order'] = $data['order'] ?? ($this->blogCategoryRepository->getMaxOrder() + 1);

            return $this->blogCategoryRepository->create($data);
        });
    }

    public function update(BlogCategory $category, array $data): BlogCategory
    {
        return DB::transaction(function () use ($category, $data) {
            if ($category->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category = $this->blogCategoryRepository->update($category, $data);

            return $category->fresh();
        });
    }

    public function destroy(BlogCategory $category): void
    {
        if ($this->blogCategoryRepository->hasPosts($category)) {
            throw new \RuntimeException('این دسته‌بندی دارای مقاله است و نمی‌توان آن را حذف کرد.');
        }

        $this->blogCategoryRepository->delete($category);
    }
}
