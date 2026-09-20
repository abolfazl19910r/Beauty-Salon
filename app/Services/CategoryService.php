<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function __construct(protected readonly CategoryRepositoryInterface $categoryRepository) {}

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $category = $this->categoryRepository->create($data);

            if (empty($data['order'])) {
                $maxOrder = $this->categoryRepository->getMaxOrder($data['parent_id'] ?? null);
                $category = $this->categoryRepository->update($category, ['order' => $maxOrder + 1]);
            }

            Log::info('دسته‌بندی جدید ایجاد شد', ['category_id' => $category->id]);

            return $category;
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            if (isset($data['parent_id']) &&
                $category->parent_id != $data['parent_id'] &&
                empty($data['order'])) {
                $maxOrder = $this->categoryRepository->getMaxOrder($data['parent_id']);
                $data['order'] = $maxOrder + 1;
            }

            $category = $this->categoryRepository->update($category, $data);

            Log::info('دسته‌بندی به‌روزرسانی شد', ['category_id' => $category->id]);

            return $category->fresh();
        });
    }

    public function toggleStatus(Category $category): Category
    {
        $category = $this->categoryRepository->update($category, [
            'is_active' => ! $category->is_active,
        ]);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            Log::info('دسته‌بندی حذف شد', ['category_id' => $category->id]);

            return $this->categoryRepository->delete($category);
        });
    }

    public function getCategoryTree(): Collection
    {
        return $this->categoryRepository->getTree();
    }

    public function getActiveCategories(): Collection
    {
        return $this->categoryRepository->getActiveTree();
    }

    public function reorderCategories(array $orderedIds): bool
    {
        try {
            DB::beginTransaction();

            foreach ($orderedIds as $index => $id) {
                $this->categoryRepository->updateOrder($id, $index + 1);
            }

            DB::commit();
            Log::info('دسته‌بندی‌ها مجددا مرتب‌سازی شدند');

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در مرتب‌سازی دسته‌بندی‌ها', [
                'error' => $e->getMessage(),
                'orderedIds' => $orderedIds,
            ]);

            return false;
        }
    }

    public function getCategorySelectOptions(): array
    {
        $categories = $this->getCategoryTree();
        $options = [];

        foreach ($categories as $category) {
            $options[$category->id] = $category->name;

            foreach ($category->children as $child) {
                $options[$child->id] = "— {$child->name}";
            }
        }

        return $options;
    }
}
