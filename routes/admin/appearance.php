<?php

use App\Http\Controllers\Admin\AdminAppearanceController;
use Illuminate\Support\Facades\Route;

// ⭐ «ظاهر سالن» (بسته‌ی «و») — انتخاب قالب سایت/پنل مشتری/متخصص/اپ و قالب پنل مدیر؛ داخل گروه salon.owner در routes/web.php.
Route::get('/appearance', [AdminAppearanceController::class, 'edit'])->name('appearance.edit');
Route::put('/appearance', [AdminAppearanceController::class, 'update'])->name('appearance.update');
