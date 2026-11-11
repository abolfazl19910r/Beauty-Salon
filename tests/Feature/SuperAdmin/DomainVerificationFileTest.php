<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\User;
use App\Support\Legal\DomainVerificationFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * تأیید مالکیت دامنه‌ی اینماد با فایل txt (۲۰۲۶-۱۱-۱۱، تصمیم ابوالفضل: نه ایمیل info@ — سرویس ایمیل روی سرور نصب نمی‌شود).
 * ابرمدیر فایل را در «تنظیمات پلتفرم» بارگذاری می‌کند و https://دامنه/<نام>.txt عیناً همان را برمی‌گرداند.
 */
class DomainVerificationFileTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    private function upload(string $name, string $content): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), [
            'verification_file' => UploadedFile::fake()->createWithContent($name, $content),
        ]);
    }

    public function test_the_uploaded_file_is_served_byte_for_byte_at_the_site_root(): void
    {
        $content = "enamad-site-verification: 7e1c9a4b2f\n";
        $this->upload('8213457.txt', $content)->assertRedirect()->assertSessionHas('success');

        auth()->logout();
        $response = $this->get('/8213457.txt')->assertOk();
        $this->assertSame($content, $response->getContent(), 'بی trim و بی تغییر');
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertNull($response->headers->get('Location'));

        $this->get('/8213458.txt')->assertNotFound();
        $this->get('/robots-x.txt')->assertNotFound();
    }

    public function test_an_empty_enamad_file_is_accepted_and_served_as_an_empty_200(): void
    {
        // ۲۰۲۶-۱۱-۱۱: فایل اینماد ‎14305914.txt خالی (۰ بایت) است و باید در https://arisheh.ir/14305914.txt باز شود
        $this->upload('14305914.txt', '')->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        auth()->logout();
        $response = $this->get('/14305914.txt')->assertOk();
        $this->assertSame('', $response->getContent());
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        $this->get('/14305915.txt')->assertNotFound();

        $this->actingAs($this->superAdmin())->get(route('superadmin.settings.edit'))->assertOk()->assertSee('فایل خالی (۰ بایت)')->assertSee('/14305914.txt', false);
    }

    public function test_an_empty_file_can_also_be_given_by_name_only(): void
    {
        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), [
            'verification_name' => '14305914.txt', 'verification_content' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('', $this->get('/14305914.txt')->assertOk()->getContent());
        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), ['remove' => '1']);
        $this->get('/14305914.txt')->assertNotFound();
    }

    public function test_saving_with_no_file_and_no_name_is_an_error(): void
    {
        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), [])
            ->assertSessionHasErrors('verification_file');
        $this->assertNull(DomainVerificationFile::current());
    }

    public function test_nothing_is_served_before_a_file_is_saved(): void
    {
        $this->get('/8213457.txt')->assertNotFound();
    }

    public function test_a_new_file_replaces_the_old_one_and_remove_turns_it_off(): void
    {
        $this->upload('111.txt', 'one')->assertRedirect();
        $this->upload('222.txt', 'two')->assertRedirect();

        $this->get('/111.txt')->assertNotFound();
        $this->assertSame('two', $this->get('/222.txt')->assertOk()->getContent());

        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), ['remove' => '1'])
            ->assertRedirect()->assertSessionHas('success');
        $this->get('/222.txt')->assertNotFound();
        $this->assertNull(DomainVerificationFile::current());
    }

    public function test_name_and_content_can_be_typed_by_hand(): void
    {
        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), [
            'verification_name' => ' 4455.txt ',
            'verification_content' => "line1\r\nline2",
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame("line1\nline2", $this->get('/4455.txt')->assertOk()->getContent());
    }

    public function test_unsafe_or_reserved_names_and_bad_content_are_rejected(): void
    {
        foreach (['robots.txt', 'x.php', 'x.txt.php', '.env.txt', '..txt', 'x.html'] as $name) {
            $this->upload($name, 'abc')->assertSessionHasErrors('verification_file');
        }
        // نام دستی با پوشه (بارگذاری مرورگر خودش فقط نام فایل را می‌فرستد)
        foreach (['a/b.txt', '../b.txt', 'b.txt/'] as $name) {
            $this->actingAs($this->superAdmin())->put(route('superadmin.settings.verification-file'), [
                'verification_name' => $name, 'verification_content' => 'abc',
            ])->assertSessionHasErrors('verification_file');
        }
        $this->upload('ok.txt', str_repeat('a', 5000))->assertSessionHasErrors('verification_file');
        $this->upload('ok.txt', "bin\x00ary")->assertSessionHasErrors('verification_file');

        $this->assertNull(DomainVerificationFile::current());
    }

    public function test_only_the_super_admin_can_change_it(): void
    {
        $salonAdmin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($salonAdmin)->put(route('superadmin.settings.verification-file'), [
            'verification_file' => UploadedFile::fake()->createWithContent('1.txt', 'x'),
        ])->assertForbidden();
        $this->assertNull(DomainVerificationFile::current());
    }

    public function test_the_settings_page_shows_the_card_and_the_public_url(): void
    {
        $this->upload('9090.txt', 'verify-me')->assertRedirect();

        $this->actingAs($this->superAdmin())->get(route('superadmin.settings.edit'))
            ->assertOk()
            ->assertSee('فایل تأیید مالکیت دامنه (اینماد)')
            ->assertSee('/9090.txt', false)
            ->assertSee('verify-me');
    }
}
