<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;

class AdminGalleryController extends Controller
{
    public function index()
    {
        return view('admin.gallery.index');
    }
}
