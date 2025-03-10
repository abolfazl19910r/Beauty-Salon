<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class AdminAnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index');
    }
}
