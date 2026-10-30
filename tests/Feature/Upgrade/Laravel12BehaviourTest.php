<?php

namespace Tests\Feature\Upgrade;

use App\Models\PushMessage;
use App\Models\User;
use App\Services\Push\PushMode;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * دو تغییر رفتاری Laravel 12 که با تصمیم ابوالفضل (۲۰۲۶-۱۰-۲۸، بسته‌ی 5ج) پذیرفته شدند و اینجا قفل می‌شوند:
 * ۱. HasUuids شناسه‌ی UUID نسخه‌ی ۷ (به ترتیب زمان) می‌سازد و پیام‌های اپ با شناسه مرتب می‌شوند؛
 * ۲. قانون image دیگر SVG را نمی‌پذیرد (SVG آپلودی در storage عمومی راه XSS است).
 */
class Laravel12BehaviourTest extends TestCase
{
    use RefreshDatabase;

    private function message(User $user): PushMessage
    {
        return PushMessage::create(['user_id' => $user->id, 'app' => 'customer', 'urgency' => 'info', 'mode' => PushMode::LIVE, 'title' => 't', 'body' => 'b']);
    }

    public function test_push_message_ids_are_time_ordered_uuid_v7(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer']);

        $created = collect(range(1, 5))->map(fn () => $this->message($user)->id);

        foreach ($created as $id) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
        }

        // هر پنج پیام در یک ثانیه (created_at یکسان) ساخته شدند؛ ترتیب شناسه همان ترتیب ساخت است
        $this->assertSame(1, PushMessage::query()->distinct()->count('created_at'));
        $this->assertSame($created->reverse()->values()->all(), PushMessage::query()->orderByDesc('id')->pluck('id')->all());
    }

    public function test_an_svg_is_rejected_by_the_image_rule(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs($admin)->from('/admin/gallery')
            ->post('/admin/gallery', ['title' => 'svg', 'image' => $svg])
            ->assertSessionHasErrors('image');
        $this->assertDatabaseCount('gallery_images', 0);

        $this->post('/admin/gallery', ['title' => 'png', 'image' => UploadedFile::fake()->image('photo.png')])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gallery_images', 1);
    }

    /** ⭐ ۲۰۲۶-۱۰-۳۱ (بند «5ج»، تصمیم ابوالفضل): PNG با پسوند svg هم رد شود — پسوند فایل هم مثل محتوا فقط تصویر رایج */
    public function test_a_png_named_svg_is_rejected_with_a_persian_message(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $fake = UploadedFile::fake()->createWithContent('photo.svg', UploadedFile::fake()->image('x.png')->getContent());

        foreach (['/admin/gallery' => ['title' => 'x'], '/admin/blog' => ['title' => 'x'], '/admin/categories' => ['name' => 'x'], '/admin/services' => ['name' => 'x']] as $url => $fields) {
            $this->actingAs($admin)->from($url)->post($url, $fields + ['image' => $fake])
                ->assertSessionHasErrors(['image' => 'پسوند تصویر باید jpg، jpeg، png یا webp باشد.']);
        }
        $this->assertDatabaseCount('gallery_images', 0);
    }
}
