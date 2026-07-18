<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Gallery\StoreGalleryImageRequest;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Previously this controller just returned an empty index() view with statistics and all the real CRUD
 * * (upload/delete/sort) just returns JSON for GalleryAdmin.jsx (React).
 * * Full conversion to Blade — no complicated drag&drop, with a simple up/down button
 * * for sorting, which can be used without any additional JS.
 */
class AdminGalleryController extends Controller
{
    public function index(): View
    {
        $images = GalleryImage::orderBy('order')->get();

        return view('admin.gallery.index', [
            'images'      => $images,
            'imagesCount' => $images->count(),
            'usedSpace'   => $this->calculateUsedSpace(),
        ]);
    }

    public function store(StoreGalleryImageRequest $request): RedirectResponse
    {
        $path = $request->file('image')->store('gallery', 'public');

        GalleryImage::create([
            'title'       => $request->validated('title'),
            'description' => $request->validated('description') ?? '',
            'image_path'  => $path,
            'order'       => GalleryImage::count() + 1,
        ]);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'تصویر با موفقیت اضافه شد.');
    }

    public function destroy(GalleryImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return redirect()->route('admin.gallery.index')
            ->with('success', 'تصویر با موفقیت حذف شد.');
    }

    public function moveUp(GalleryImage $image): RedirectResponse
    {
        $previous = GalleryImage::where('order', '<', $image->order)
            ->orderByDesc('order')
            ->first();

        if ($previous) {
            $this->swapOrder($image, $previous);
        }

        return redirect()->route('admin.gallery.index');
    }

    public function moveDown(GalleryImage $image): RedirectResponse
    {
        $next = GalleryImage::where('order', '>', $image->order)
            ->orderBy('order')
            ->first();

        if ($next) {
            $this->swapOrder($image, $next);
        }

        return redirect()->route('admin.gallery.index');
    }

    private function swapOrder(GalleryImage $a, GalleryImage $b): void
    {
        [$orderA, $orderB] = [$a->order, $b->order];
        $a->update(['order' => $orderB]);
        $b->update(['order' => $orderA]);
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
