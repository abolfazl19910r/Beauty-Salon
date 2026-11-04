<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۴، L1: دیسک local (storage/app/private — فایل FCM، گزارش‌ها، لوگوی ثبت‌نام در انتظار) با 'serve' => true
 * مسیر امضاشده‌ی GET|PUT /storage/{path} لاراول را باز می‌کرد (با لو رفتن APP_KEY، خواندن/نوشتن هر فایل آن دیسک).
 */
class PrivateDiskNotServedTest extends TestCase
{
    public function test_the_private_disk_has_no_serving_routes(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_storage_paths_are_not_handled_by_laravel(): void
    {
        $path = '/storage/push/fcm-service-account.json?expires='.now()->addMinute()->getTimestamp().'&signature=x';

        $this->get($path)->assertNotFound();
        $this->put($path, ['file' => 'x'])->assertStatus(404);
    }
}
