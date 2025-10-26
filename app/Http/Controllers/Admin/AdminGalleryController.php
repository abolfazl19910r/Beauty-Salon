<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminGalleryController extends Controller
{
    public function index()
    {
        $stats = $this->getStats();

        return view('admin.gallery.index', [
            'imagesCount' => $stats['imagesCount'],
            'albumsCount' => $stats['albumsCount'],
            'usedSpace' => $stats['usedSpace']
        ]);
    }

    public function stats()
    {
        return response()->json($this->getStats());
    }

    private function getStats(): array
    {
        $imagesCount = GalleryImage::count();
        $albumsCount = 0;
        $usedSpace = $this->calculateUsedSpace();

        return [
            'imagesCount' => $imagesCount,
            'albumsCount' => $albumsCount,
            'usedSpace' => $usedSpace
        ];
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

    public function getImages()
    {
        $images = GalleryImage::orderBy('order')->get()->map(function($image) {
            return [
                'id' => $image->id,
                'title' => $image->title,
                'description' => $image->description,
                'image_url' => $image->image_url,
                'order' => $image->order
            ];
        });

        return response()->json($images);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $path = $request->file('image')->store('gallery', 'public');

        $image = GalleryImage::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'image_path' => $path,
            'order' => GalleryImage::count() + 1
        ]);

        return response()->json([
            'id' => $image->id,
            'title' => $image->title,
            'description' => $image->description,
            'image_url' => $image->image_url,
            'order' => $image->order
        ], 201);
    }

    public function destroy($id)
    {
        $image = GalleryImage::findOrFail($id);
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request)
    {
        $images = $request->input('images');

        foreach ($images as $imageData) {
            GalleryImage::where('id', $imageData['id'])
                ->update(['order' => $imageData['order']]);
        }

        return response()->json(['message' => 'ترتیب با موفقیت به‌روز شد']);
    }
}
