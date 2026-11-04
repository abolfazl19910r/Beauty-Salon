<?php

namespace Tests\Feature\Security;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۴ — H1 (بلاگ سالن) و H2 (نام مشتری در اعلان‌ها و داشبورد).
 * متن آزاد هر کاربر (مدیر سالن دیگر، مشتری) هم‌مبدا با /admin و /superadmin نمایش داده می‌شود؛ باید escape شود.
 */
class StoredXssTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<script>alert(1)</script><img src=x onerror=alert(2)>';

    public function test_public_blog_post_content_is_rendered_as_escaped_plain_text(): void
    {
        $post = BlogPost::factory()->create([
            'category_id' => BlogCategory::factory()->create()->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
            'content' => "خط اول\n".self::PAYLOAD,
        ]);

        $html = $this->get(route('blog.show', $post))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        // متن ساده: شکست خط همچنان نمایش داده می‌شود
        $this->assertStringContainsString('خط اول<br />', $html);
    }

    public function test_admin_blog_preview_escapes_post_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = BlogPost::factory()->create([
            'category_id' => BlogCategory::factory()->create()->id,
            'content' => self::PAYLOAD,
        ]);

        $html = $this->actingAs($admin)->get("/admin/blog/{$post->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $html);
    }

    public function test_admin_notification_list_escapes_the_message(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        UserNotification::factory()->create([
            'user_id' => $admin->id,
            'notifiable_id' => $admin->id,
            'data' => ['message' => 'نوبت جدید توسط '.self::PAYLOAD, 'link' => '#'],
        ]);

        $html = $this->actingAs($admin)->get('/admin/notifications')->assertOk()->getContent();

        $this->assertStringNotContainsString('<img src=x onerror=alert(2)>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $html);
    }

    public function test_notification_bells_escape_message_before_inner_html(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $html = $this->actingAs($admin)->get('/admin/notifications')->assertOk()->getContent();

        $this->assertStringContainsString('function escHtml(v)', $html);
        $this->assertStringContainsString('${escHtml(notification.message)}', $html);
        $this->assertStringNotContainsString('${notification.message}', $html);

        $specialistLayout = file_get_contents(resource_path('views/layouts/specialist.blade.php'));
        $this->assertStringContainsString('function escHtml(v)', $specialistLayout);
        $this->assertStringContainsString('${escHtml(notification.message)}', $specialistLayout);
        $this->assertStringNotContainsString('${notification.message}', $specialistLayout);
    }

    public function test_admin_dashboard_escapes_customer_and_service_names_in_live_widgets(): void
    {
        $dashboard = file_get_contents(resource_path('views/admin/dashboard.blade.php'));

        foreach (['${b.user_name', '${s.name}', '${b.service_name'] as $raw) {
            $this->assertStringNotContainsString($raw, $dashboard, "sink بی escape: {$raw}");
        }
        $this->assertStringContainsString("\${escHtml(b.user_name || 'کاربر ناشناس')}", $dashboard);
        $this->assertStringContainsString('${escHtml(s.name)}', $dashboard);
    }

    public function test_escape_helper_neutralises_html_special_characters(): void
    {
        // همان تابع JS با node اجرا می‌شود (اگر node در دسترس باشد)
        $node = trim((string) shell_exec('command -v node 2>/dev/null'));
        if ($node === '') {
            $this->markTestSkipped('node نیست');
        }
        preg_match('/function escHtml\(v\) \{.*?\[c\]\)\); \}/s', file_get_contents(resource_path('views/layouts/admin.blade.php')), $m);
        $this->assertNotEmpty($m, 'escHtml در layout مدیر پیدا نشد');

        $js = $m[0].' process.stdout.write(escHtml('.json_encode(self::PAYLOAD."\"'&").') + "|" + escHtml(null) + "|" + escHtml(12));';
        $out = shell_exec(escapeshellarg($node).' -e '.escapeshellarg($js));

        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;&lt;img src=x onerror=alert(2)&gt;&quot;&#39;&amp;||12', $out);
    }
}
