<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $category = Category::create($data);

            if (empty($data['order'])) {
                $maxOrder = Category::where('parent_id', $data['parent_id'] ?? null)->max('order') ?? 0;
                $category->update(['order' => $maxOrder + 1]);
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
                $maxOrder = Category::where('parent_id', $data['parent_id'])->max('order') ?? 0;
                $data['order'] = $maxOrder + 1;
            }

            $category->update($data);

            Log::info('دسته‌بندی به‌روزرسانی شد', ['category_id' => $category->id]);

            return $category->fresh();
        });
    }

    public function toggleStatus(Category $category): Category
    {
        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            Log::info('دسته‌بندی حذف شد', ['category_id' => $category->id]);

            return $category->delete();
        });
    }

    public function getCategoryTree(): Collection
    {
        return Category::with('children')
            ->parents()
            ->orderBy('order')
            ->get();
    }

    public function getActiveCategories(): Collection
    {
        return Category::with(['children' => function ($query) {
            $query->where('is_active', true)->orderBy('order');
        }])
            ->parents()
            ->active()
            ->orderBy('order')
            ->get();
    }

    public function reorderCategories(array $orderedIds): bool
    {
        try {
            DB::beginTransaction();

            foreach ($orderedIds as $index => $id) {
                Category::where('id', $id)->update(['order' => $index + 1]);
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
