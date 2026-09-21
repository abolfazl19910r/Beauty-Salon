<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Gallery\StoreGalleryImageRequest;
use App\Models\GalleryImage;
use App\Repositories\Contracts\GalleryImageRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminGalleryController extends Controller
{
    public function __construct(
        private readonly GalleryImageRepositoryInterface $galleryImageRepository,
    ) {}

    public function index(): View
    {
        $images = $this->galleryImageRepository->getAllOrdered();

        return view('admin.gallery.index', [
            'images' => $images,
            'imagesCount' => $images->count(),
            'usedSpace' => $this->calculateUsedSpace(),
        ]);
    }

    public function store(StoreGalleryImageRequest $request): RedirectResponse
    {
        $path = $request->file('image')->store('gallery', 'public');

        $this->galleryImageRepository->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description') ?? '',
            'image_path' => $path,
            'order' => $this->galleryImageRepository->count() + 1,
        ]);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'تصویر با موفقیت اضافه شد.');
    }

    public function destroy(GalleryImage $image): RedirectResponse
    {
        $this->ensureSalonOwnership($image->salon_id);

        Storage::disk('public')->delete($image->image_path);
        $this->galleryImageRepository->delete($image);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'تصویر با موفقیت حذف شد.');
    }

    public function moveUp(GalleryImage $image): RedirectResponse
    {
        $this->ensureSalonOwnership($image->salon_id);

        $previous = $this->galleryImageRepository->findPreviousByOrder($image->order);

        if ($previous) {
            $this->swapOrder($image, $previous);
        }

        return redirect()->route('admin.gallery.index');
    }

    public function moveDown(GalleryImage $image): RedirectResponse
    {
        $this->ensureSalonOwnership($image->salon_id);

        $next = $this->galleryImageRepository->findNextByOrder($image->order);

        if ($next) {
            $this->swapOrder($image, $next);
        }

        return redirect()->route('admin.gallery.index');
    }

    private function swapOrder(GalleryImage $a, GalleryImage $b): void
    {
        [$orderA, $orderB] = [$a->order, $b->order];
        $this->galleryImageRepository->update($a, ['order' => $orderB]);
        $this->galleryImageRepository->update($b, ['order' => $orderA]);
    }

    private function calculateUsedSpace(): float
    {
        $totalSize = 0;
        $files = Storage::disk('public')->allFiles('gallery');

        foreach ($files as $file) {
            try {
                $totalSize += Storage::disk('public')->size($file);
            } catch (\Exception $e) {
                continue;
            }
        }

        return round($totalSize / (1024 * 1024), 2);
    }
}
