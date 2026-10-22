<?php

namespace Tests\Feature\Central;

use App\Models\AppRelease;
use App\Models\Role;
use App\Models\User;
use App\Services\Apps\ApkManifestReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFakeApk;
use Tests\TestCase;

/**
 * بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): اپ‌ها بدون مارکت — APK آپلودی ابرمدیر، صفحه‌ی «دانلود اپ ماهرو»، API نسخه‌ی تازه، assetlinks.json.
 */
class AppDistributionTest extends TestCase
{
    use BuildsFakeApk;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    private function upload(array $overrides = [], ?\Illuminate\Http\UploadedFile $file = null)
    {
        return $this->post(route('superadmin.app-releases.store'), array_merge([
            'variant' => 'customer',
            'apk' => $file ?? $this->fakeApkUpload(),
            'notes' => 'رزرو سریع‌تر',
        ], $overrides));
    }

    public function test_manifest_reader_handles_utf16_utf8_and_blank_attribute_names(): void
    {
        $this->assertSame(['package' => 'ir.mahru', 'version_code' => 7, 'version_name' => '0.2.5'],
            ApkManifestReader::fromAxml($this->fakeAxml('ir.mahru', 7, '0.2.5')));
        $this->assertSame(['package' => 'ir.mahru.staff', 'version_code' => 12, 'version_name' => '1.0.0'],
            ApkManifestReader::fromAxml($this->fakeAxml('ir.mahru.staff', 12, '1.0.0', utf8: true)));
        $this->assertSame(['package' => 'ir.mahru', 'version_code' => 9, 'version_name' => '0.3.1'],
            ApkManifestReader::fromAxml($this->fakeAxml('ir.mahru', 9, '0.3.1', blankNames: true)));

        $this->assertNull(ApkManifestReader::fromAxml('not an axml file'));
        $this->assertNull(ApkManifestReader::fromAxml(substr($this->fakeAxml('ir.mahru', 7, '0.2.5'), 0, 60)));
    }

    public function test_super_admin_uploads_apk_and_version_is_read_from_the_file(): void
    {
        $this->actingAs($this->superAdmin());

        $this->upload()->assertRedirect(route('superadmin.app-releases.index'));

        $release = AppRelease::sole();
        $this->assertSame(['customer', 8, '0.3.0', 'رزرو سریع‌تر', true, false],
            [$release->variant, $release->version_code, $release->version_name, $release->notes, $release->is_published, $release->is_required]);
        Storage::disk('local')->assertExists($release->file_path);
        $this->assertSame(64, strlen($release->sha256));
        $this->assertDatabaseHas('superadmin_audit_logs', ['action' => 'app_release.upload']);

        $this->get(route('superadmin.app-releases.index'))->assertOk()->assertSee('data-app-release="'.$release->id.'"', false)
            ->assertSee($release->sha256);
    }

    public function test_upload_is_rejected_for_wrong_app_duplicate_version_and_non_apk(): void
    {
        $this->actingAs($this->superAdmin());

        // APK اپ همکار زیر اپ مشتری
        $this->upload([], $this->fakeApkUpload('ir.mahru.staff'))->assertSessionHasErrors('apk');
        // فایل zip که APK نیست
        $zip = tempnam(sys_get_temp_dir(), 'zip');
        $z = new \ZipArchive;
        $z->open($zip, \ZipArchive::OVERWRITE);
        $z->addFromString('readme.txt', 'x');
        $z->close();
        $this->upload([], new \Illuminate\Http\UploadedFile($zip, 'x.apk', null, null, true))->assertSessionHasErrors('apk');
        // پسوند غیر apk
        $this->upload([], new \Illuminate\Http\UploadedFile($this->fakeApkFile(), 'x.zip', null, null, true))->assertSessionHasErrors('apk');
        $this->assertSame(0, AppRelease::count());

        $this->upload()->assertSessionHasNoErrors();
        $this->upload([], $this->fakeApkUpload(versionCode: 8))->assertSessionHasErrors('apk');
        // شماره‌ی دستی مخالف خود فایل
        $this->upload(['version_code' => 11], $this->fakeApkUpload(versionCode: 10))->assertSessionHasErrors('version_code');
        $this->assertSame(1, AppRelease::count());
    }

    public function test_unreadable_manifest_needs_manual_version(): void
    {
        $this->actingAs($this->superAdmin());
        $file = fn () => $this->fakeApkUpload(manifest: 'unreadable');

        $this->upload([], $file())->assertSessionHasErrors('version_code');
        $this->upload(['version_code' => 5, 'version_name' => '0.1.9'], $file())->assertSessionHasNoErrors();
        $this->assertSame([5, '0.1.9'], [AppRelease::sole()->version_code, AppRelease::sole()->version_name]);
    }

    public function test_upload_larger_than_php_post_limit_returns_to_the_page_with_a_message(): void
    {
        $this->actingAs($this->superAdmin());

        $this->call('POST', route('superadmin.app-releases.store'), [], [], [], ['CONTENT_LENGTH' => (string) (20 * 1024 * 1024 * 1024)])
            ->assertRedirect('/superadmin/app-releases?too_large=1');
        $this->get('/superadmin/app-releases?too_large=1')->assertOk()->assertSee('data-upload-too-large', false);
    }

    public function test_only_super_admin_can_manage_releases(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('superadmin.app-releases.index'))->assertForbidden();
        $this->upload()->assertForbidden();
    }

    public function test_download_page_serves_latest_published_release_and_counts(): void
    {
        $this->get(route('app-download.show'))->assertOk()->assertSee('data-app-download-soon', false)->assertDontSee('data-app-download-customer', false);
        $this->get(route('app-download.file', ['variant' => 'customer']))->assertNotFound();

        $admin = $this->superAdmin();
        $this->actingAs($admin);
        $this->upload([], $this->fakeApkUpload(versionCode: 8, versionName: '0.3.0'));
        $this->upload([], $this->fakeApkUpload(versionCode: 9, versionName: '0.3.1'));
        $this->post(route('superadmin.app-releases.store'), ['variant' => 'staff', 'apk' => $this->fakeApkUpload('ir.mahru.staff', 4, '0.2.0')]);
        auth()->logout();

        $this->get(route('app-download.show', ['salon' => 'rasta']))->assertOk()
            ->assertSee('data-app-download-customer', false)->assertSee('0.3.1')->assertDontSee('0.3.0')
            ->assertSee('data-app-download-staff', false)->assertSee('data-app-download-salon-code', false)
            ->assertDontSee('SHA-256')->assertDontSee(\App\Models\AppRelease::where('version_code', 9)->value('sha256')); // فقط در پنل ابرمدیر (۲۰۲۶-۱۰-۲۲)

        $response = $this->get(route('app-download.file', ['variant' => 'customer']))->assertOk();
        $this->assertSame('application/vnd.android.package-archive', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('mahru-0.3.1.apk', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(1, AppRelease::where('version_code', 9)->value('download_count'));
        $this->assertStringContainsString('mahru-staff-0.2.0.apk',
            (string) $this->get(route('app-download.file', ['variant' => 'staff']))->headers->get('Content-Disposition'));

        // پنهان کردن نسخه‌ی تازه ← صفحه به نسخه‌ی قبل برمی‌گردد
        $this->actingAs($admin)->patch(route('superadmin.app-releases.update', AppRelease::where('version_code', 9)->sole()), ['is_published' => 0])
            ->assertRedirect();
        $this->get(route('app-download.show'))->assertSee('0.3.0');

        // حذف، فایل را هم پاک می‌کند
        $release = AppRelease::where('version_code', 8)->sole();
        $this->delete(route('superadmin.app-releases.destroy', $release))->assertRedirect();
        Storage::disk('local')->assertMissing($release->file_path);
        $this->get(route('app-download.file', ['variant' => 'customer']))->assertNotFound();
    }

    public function test_unknown_salon_code_is_not_shown_on_download_page(): void
    {
        $this->get(route('app-download.show', ['salon' => 'nist']))->assertOk()->assertDontSee('data-app-download-salon-code', false);
        $this->get(route('app-download.show', ['salon' => '<script>']))->assertOk()->assertDontSee('data-app-download-salon-code', false);
    }

    public function test_latest_version_api_reports_available_and_required_updates(): void
    {
        $this->getJson(route('api.v1.app.latest', ['variant' => 'customer', 'version_code' => 7]))->assertOk()
            ->assertJsonPath('data.latest', null)->assertJsonPath('data.update_available', false);
        $this->getJson(route('api.v1.app.latest', ['variant' => 'tablet']))->assertStatus(422);

        $this->actingAs($this->superAdmin());
        $this->upload(['is_required' => 1], $this->fakeApkUpload(versionCode: 8, versionName: '0.3.0'));
        $this->upload([], $this->fakeApkUpload(versionCode: 9, versionName: '0.3.1'));
        auth()->logout();

        $latest = fn (int $code) => $this->getJson(route('api.v1.app.latest', ['variant' => 'customer', 'version_code' => $code]))->assertOk();

        // گوشی ۷: نسخه‌ی اجباری ۸ را رد کرده ← اجباری، حتی اگر آخرین (۹) اجباری نیست
        $latest(7)->assertJsonPath('data.update_available', true)->assertJsonPath('data.update_required', true)
            ->assertJsonPath('data.latest.version_code', 9)->assertJsonPath('data.latest.version_name', '0.3.1');
        $latest(8)->assertJsonPath('data.update_available', true)->assertJsonPath('data.update_required', false);
        $latest(9)->assertJsonPath('data.update_available', false)->assertJsonPath('data.update_required', false);
        $this->assertStringEndsWith('/app/download/customer', $latest(7)->json('data.latest.download_url'));
    }

    public function test_assetlinks_lists_normalized_fingerprints(): void
    {
        $this->get('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);

        config(['api.customer_app_sha256_fingerprints' => [
            '14:6d:e9:83:c5:73:06:50:d8:ee:b9:95:2f:34:fc:64:16:a0:83:42:e6:1d:be:a8:8a:04:96:b2:3f:cf:44:e5',
            str_repeat('AB', 32),
            'nadorost',
        ]]);

        $response = $this->get('/.well-known/assetlinks.json')->assertOk();
        $this->assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
        $response->assertExactJson([[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => 'ir.mahru',
                'sha256_cert_fingerprints' => [
                    '14:6D:E9:83:C5:73:06:50:D8:EE:B9:95:2F:34:FC:64:16:A0:83:42:E6:1D:BE:A8:8A:04:96:B2:3F:CF:44:E5',
                    implode(':', array_fill(0, 32, 'AB')),
                ],
            ],
        ]]);
    }
}
