<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $images = GalleryImage::orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($images);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|max:2048',
            'order' => 'nullable|integer'
        ]);

        $validated['image_path'] = $request->file('image')->store('gallery', 'public');

        $image = GalleryImage::create($validated);

        return response()->json($image, 201);
    }

    public function update(Request $request, GalleryImage $image): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'order' => 'nullable|integer'
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($image->image_path);
            $validated['image_path'] = $request->file('image')->store('gallery', 'public');
        }

        $image->update($validated);

        return response()->json($image);
    }

    public function destroy(GalleryImage $image): JsonResponse
    {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json(['message' => 'تصویر با موفقیت حذف شد']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:gallery_images,id',
            'images.*.order' => 'required|integer'
        ]);

        foreach ($request->images as $imageData) {
            GalleryImage::where('id', $imageData['id'])
                ->update(['order' => $imageData['order']]);
        }

        return response()->json(['message' => 'ترتیب تصاویر با موفقیت بروزرسانی شد']);
    }
}
