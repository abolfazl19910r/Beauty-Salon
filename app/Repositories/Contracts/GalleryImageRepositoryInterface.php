<?php

namespace App\Repositories\Contracts;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Collection;

interface GalleryImageRepositoryInterface extends RepositoryInterface
{
    public function getAllOrdered(): Collection;

    public function findPreviousByOrder(int $order): ?GalleryImage;

    public function findNextByOrder(int $order): ?GalleryImage;
}
