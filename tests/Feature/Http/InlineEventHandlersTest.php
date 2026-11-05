<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ بررسی امنیتی H3 مرحله‌ی ۲ (۲۰۲۶-۱۱-۰۵): با CSP سخت‌گیر (script-src فقط nonce) هیچ handler درون‌خطی (onclick=، onmouseover=،
 * onsubmit=، …) و هیچ نشانی javascript: اجرا نمی‌شود — پس هیچ‌کدام نباید در viewها بماند. جایگزین: data-ui-* و
 * partials/ui-handlers که باید در <head> هر سند HTML باشد.
 */
class InlineEventHandlersTest extends TestCase
{
    use RefreshDatabase;

    /** سندهایی که HTML صفحه‌ی مرورگر نیستند (PDF با mPDF، خروجی اکسل، ایمیل) */
    private const NOT_BROWSER_DOCUMENTS = [
        'admin/reports/pdf-report.blade.php',
        'specialist/reports/pdf.blade.php',
        'exports/report.blade.php',
    ];

    /** @return array<string, string> مسیر نسبی ← متن بدون کامنت Blade */
    private function views(): array
    {
        $views = [];
        $root = resource_path('views').'/';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $source = (string) file_get_contents($file->getPathname());
                $views[str_replace($root, '', $file->getPathname())] = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);
            }
        }
        ksort($views);

        return $views;
    }

    /** handler درون‌خطی در markup یا در رشته‌ی HTML داخل JS (مثل innerHTML = '<button onclick="…">') */
    private const HANDLER = '/(?<=[\s"\'\/])on[a-z]{3,}\s*=\s*(["\']|\\\\["\'])/i';

    public function test_no_view_has_an_inline_event_handler(): void
    {
        $found = [];
        foreach ($this->views() as $path => $source) {
            foreach (explode("\n", $source) as $i => $line) {
                if (preg_match(self::HANDLER, $line, $m)) {
                    $found[] = $path.':'.($i + 1).' '.trim(mb_substr($line, 0, 120));
                }
            }
        }

        $this->assertSame([], $found, 'به جای on…= از data-ui-* (partials/ui-handlers) استفاده کنید');
    }

    public function test_no_view_has_a_javascript_url(): void
    {
        $found = [];
        foreach ($this->views() as $path => $source) {
            if (preg_match_all('/\b(?:href|src|action|formaction)\s*=\s*["\']\s*javascript:/i', $source, $m)) {
                $found[] = $path;
            }
        }

        $this->assertSame([], $found);
    }

    public function test_the_pattern_catches_what_it_must_and_ignores_data_ui_attributes(): void
    {
        foreach (['<a onclick="x()">', "<a  onMouseOver='x'>", '"<button onclick=\"go()\">"', '<input/onfocus="x">', '<form onsubmit = "return confirm(1)">'] as $bad) {
            $this->assertMatchesRegularExpression(self::HANDLER, $bad, $bad);
        }
        foreach (['<a data-ui-click="x">', 'xhr.onload = function () {}', 'button.onclick = go;', '<div data-ui-hover="background: red">', "const option = '1';", 'data-confirm-message="x"'] as $ok) {
            $this->assertDoesNotMatchRegularExpression(self::HANDLER, $ok, $ok);
        }
    }

    public function test_every_browser_document_loads_the_shared_handlers_in_its_head(): void
    {
        $missing = [];
        foreach ($this->views() as $path => $source) {
            if (! preg_match('/<html[\s>]/i', $source) || in_array($path, self::NOT_BROWSER_DOCUMENTS, true)
                || str_starts_with($path, 'vendor/mail/') || str_starts_with($path, 'emails/')) {
                continue;
            }
            $head = preg_match('/<head[\s>].*?<\/head>/is', $source, $m) ? $m[0] : '';
            if (! str_contains($head, "@include('partials.ui-handlers')")) {
                $missing[] = $path;
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_rendered_pages_carry_the_handlers_with_the_nonce_and_no_inline_handler(): void
    {
        config(['security.csp.strict_scripts' => 'enforce']);
        $salon = \App\Models\Salon::factory()->create();

        foreach (['/', '/login', '/s/'.$salon->slug, '/s/'.$salon->slug.'/login', '/offline'] as $uri) {
            $response = $this->get($uri)->assertOk();
            $html = (string) $response->getContent();
            preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $nonce);

            $this->assertNotEmpty($nonce, $uri);
            $this->assertMatchesRegularExpression('/<script data-ui-handlers nonce="'.preg_quote($nonce[1], '/').'">/', $html, $uri);
            $this->assertSame(1, substr_count($html, 'data-ui-handlers'), $uri.': یک بار در هر صفحه');
            $this->assertDoesNotMatchRegularExpression(self::HANDLER, $html, $uri);
        }
    }
}
