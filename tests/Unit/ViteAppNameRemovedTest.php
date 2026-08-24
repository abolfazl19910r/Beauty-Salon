<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression guard (post-test-writing-phase cleanup, item 2 of the phase's final leftovers):
 * VITE_APP_NAME was a completely unused .env.example key — grep across app/, resources/js/,
 * vite.config.js, and every config/*.php file confirmed zero reads anywhere in the project
 * (not even implicitly via import.meta.env in a .jsx file). Removed from both .env.example and
 * .env rather than left as a dead, misleading placeholder.
 */
class ViteAppNameRemovedTest extends TestCase
{
    public function test_vite_app_name_is_not_present_in_env_example(): void
    {
        $contents = file_get_contents(base_path('.env.example'));

        $this->assertStringNotContainsString('VITE_APP_NAME', $contents);
    }

    public function test_vite_app_name_is_not_read_anywhere_in_the_codebase(): void
    {
        $matches = [];

        foreach (['app', 'config', 'resources/js', 'vite.config.js'] as $path) {
            $fullPath = base_path($path);

            if (is_file($fullPath)) {
                if (str_contains(file_get_contents($fullPath), 'VITE_APP_NAME')) {
                    $matches[] = $path;
                }

                continue;
            }

            if (! is_dir($fullPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && str_contains(file_get_contents($file->getPathname()), 'VITE_APP_NAME')) {
                    $matches[] = $file->getPathname();
                }
            }
        }

        $this->assertEmpty($matches, 'VITE_APP_NAME is still referenced in: '.implode(', ', $matches));
    }
}
