<?php

namespace App\Repositories\Eloquent;

use App\Models\GalleryImage;
use App\Repositories\Contracts\GalleryImageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GalleryImageRepository extends BaseRepository implements GalleryImageRepositoryInterface
{
    public function __construct(GalleryImage $model)
    {
        parent::__construct($model);
    }

    public function getAllOrdered(): Collection
    {
        return $this->model->orderBy('order')->get();
    }

    public function findPreviousByOrder(int $order): ?GalleryImage
    {
        return $this->model->where('order', '<', $order)
            ->orderByDesc('order')
            ->first();
    }

    public function findNextByOrder(int $order): ?GalleryImage
    {
        return $this->model->where('order', '>', $order)
            ->orderBy('order')
            ->first();
    }
}
