<?php

namespace Tests\Feature\Specialist;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * گزارش ابوالفضل ۲۰۲۶-۱۰-۲۹: مدیر شماره‌ی متخصص را عوض کرد، ولی حساب ورود متخصص همان شماره‌ی قدیمی را نگه داشت (و از پرونده‌ی متخصص هم
 * جدا شد)؛ «فراموشی رمز» با شماره‌ی تازه «کاربری با این شماره یافت نشد» می‌داد. مدیر رمز متخصص را هم نمی‌توانست عوض کند.
 * تصمیم: حساب شماره‌ی تازه را می‌گیرد و مدیر همیشه می‌تواند رمز بگذارد — فقط برای حسابی که مال همین سالن است؛ نشست‌ها و توکن‌ها بسته
 * و به متخصص پیامک خبر می‌رود.
 */
class AdminSpecialistAccountEditTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = '09351110000';

    private const NEW = '09352220000';

    private User $owner;

    private User $account;

    private Specialist $specialist;

    /** @var list<array{0: string, 1: string}> */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $text) {
                $this->sms[] = [$phone, $text];

                return true;
            });
            $mock->shouldIgnoreMissing(true);
        });

        $salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'phone' => '09120000001']);
        $this->account = User::factory()->create([
            'user_type' => 'staff', 'salon_id' => $salon->id, 'phone' => self::OLD, 'password' => Hash::make('old-pass-1'),
        ]);
        $this->specialist = Specialist::factory()->create(['phone' => self::OLD, 'user_id' => $this->account->id, 'email' => 'sara@example.com']);
    }

    private function form(array $extra = []): array
    {
        return array_merge([
            'name' => $this->specialist->name, 'phone' => self::OLD, 'email' => 'sara@example.com',
            'services' => [BeautyService::factory()->create()->id],
        ], $extra);
    }

    private function save(array $extra = [])
    {
        return $this->actingAs($this->owner)->put(route('admin.specialists.update', $this->specialist), $this->form($extra));
    }

    public function test_the_login_account_follows_a_new_phone_and_password_reset_finds_it(): void
    {
        $this->account->createToken('staff-app');
        DB::table('sessions')->insert(['id' => 'sess-1', 'user_id' => $this->account->id, 'payload' => '', 'last_activity' => time()]);

        $this->save(['phone' => self::NEW])->assertRedirect(route('admin.specialists.index'))->assertSessionHasNoErrors();

        $account = $this->account->fresh();
        $this->assertSame(self::NEW, $account->phone);
        $this->assertSame($account->id, $this->specialist->fresh()->user_id, 'پرونده‌ی متخصص به همان حساب وصل می‌ماند');
        $this->assertSame(0, $account->tokens()->count(), 'توکن اپ همکار بسته شد');
        $this->assertDatabaseMissing('sessions', ['id' => 'sess-1']);
        $this->assertTrue(Hash::check('old-pass-1', $account->password), 'رمز همان می‌ماند');
        $phones = array_column($this->sms, 0);
        $this->assertContains(self::OLD, $phones, 'خبر به شماره‌ی قبلی');
        $this->assertContains(self::NEW, $phones, 'خبر به شماره‌ی تازه');

        auth()->logout();
        $this->post(route('password.send'), ['phone' => self::NEW])->assertSessionHasNoErrors();
    }

    public function test_a_legacy_specialist_linked_only_by_phone_keeps_its_account(): void
    {
        $this->specialist->forceFill(['user_id' => null])->save();

        $this->save(['phone' => self::NEW])->assertSessionHasNoErrors();

        $this->assertSame(self::NEW, $this->account->fresh()->phone);
        $this->assertSame($this->account->id, $this->specialist->fresh()->user_id);
    }

    public function test_the_admin_sets_a_new_password_for_an_existing_account(): void
    {
        $this->account->createToken('staff-app');

        $this->save(['initial_password' => 'new-pass-22', 'initial_password_confirmation' => 'new-pass-22'])->assertSessionHasNoErrors();

        $account = $this->account->fresh();
        $this->assertTrue(Hash::check('new-pass-22', $account->password));
        $this->assertSame(0, $account->tokens()->count());
        $this->assertContains(self::OLD, array_column($this->sms, 0), 'خبر تغییر رمز به متخصص');
        $this->actingAs($this->owner)->get(route('admin.specialists.edit', $this->specialist))->assertOk()->assertSee('name="initial_password"', false);
    }

    public function test_saving_without_changes_leaves_the_account_alone(): void
    {
        $this->account->createToken('staff-app');

        $this->save()->assertSessionHasNoErrors();

        $this->assertSame(1, $this->account->fresh()->tokens()->count());
        $this->assertSame([], $this->sms);
    }

    public function test_an_account_used_elsewhere_is_never_changed_by_one_salon(): void
    {
        $other = Salon::factory()->create();
        // BelongsToSalon سالن جاری را روی create می‌نشاند؛ ردیف سالن دیگر مستقیم
        DB::table('specialists')->insert(['salon_id' => $other->id, 'name' => 'سارا', 'phone' => '09359990000', 'user_id' => $this->account->id,
            'email' => 'sara2@example.com', 'created_at' => now(), 'updated_at' => now()]);

        $this->save(['phone' => self::NEW])->assertSessionHasErrors('phone');
        $this->save(['initial_password' => 'new-pass-22', 'initial_password_confirmation' => 'new-pass-22'])->assertSessionHasErrors('initial_password');

        $this->assertSame(self::OLD, $this->account->fresh()->phone);
        $this->assertSame(self::OLD, $this->specialist->fresh()->phone);
        $this->assertTrue(Hash::check('old-pass-1', $this->account->fresh()->password));
    }

    public function test_a_phone_that_already_has_another_account_is_refused(): void
    {
        User::factory()->create(['user_type' => 'staff', 'phone' => self::NEW]);

        $this->save(['phone' => self::NEW])->assertSessionHasErrors('phone');

        $this->assertSame(self::OLD, $this->account->fresh()->phone);
        $this->assertSame($this->account->id, $this->specialist->fresh()->user_id, 'از حساب خودش جدا نشد');
    }

    public function test_a_specialist_without_an_account_gets_one_from_the_edit_form(): void
    {
        $this->specialist->forceFill(['user_id' => null, 'phone' => '09353330000'])->save();

        $this->save(['phone' => '09353330000', 'initial_password' => 'first-pass-1', 'initial_password_confirmation' => 'first-pass-1'])
            ->assertSessionHasNoErrors();

        $user = User::where('phone', '09353330000')->where('user_type', 'staff')->sole();
        $this->assertSame($user->id, $this->specialist->fresh()->user_id);
        $this->assertTrue(Hash::check('first-pass-1', $user->password));
    }
}
