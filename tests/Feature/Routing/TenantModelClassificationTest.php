<?php

namespace Tests\Feature\Routing;

use App\Traits\BelongsToSalon;
use App\Traits\BelongsToSalonThroughSpecialist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * هر مدل باید آگاهانه طبقه‌بندی شده باشد: یا scope سالن دارد (BelongsToSalon / BelongsToSalonThroughSpecialist)،
 * یا در فهرست پایین با دلیل آمده است. نشت‌های ممیزی ۲۰۲۶-۰۹-۲۶/۲۷ دقیقاً از جدول‌هایی بود که کسی درباره‌شان تصمیم
 * نگرفته بود (برداشت‌ها، کیف پول‌ها، نظرات، مرخصی‌ها). مدل تازه بدون تصمیم، این تست را می‌شکند.
 */
class TenantModelClassificationTest extends TestCase
{
    use RefreshDatabase;

    /** مدل‌های بدون scope سالن، با دلیل. */
    private const UNSCOPED = [
        // سراسری / پلتفرم
        'Salon' => 'خود سالن',
        'User' => 'کاربر staff سراسری است؛ «کاربرهای این سالن» با UserRepository::querySalonMembers()',
        'Permission' => 'کاتالوگ مشترک مجوزها',
        'Role' => 'نقش سیستمی (salon_id=null) + نقش سالن؛ RoleRepository صریحاً فیلتر می‌کند',
        'Loyalty' => 'جدول قدیمی بدون ستون سالن',
        // salon_id دارند ولی همیشه با salon_id صریح خوانده می‌شوند (صف/اعلان بدون CurrentSalon)
        'NotificationSetting' => 'خواندن با salon_id رکورد (settingsSalonId)',
        'PaymentTransaction' => 'callback/reconcile بدون CurrentSalon؛ salon_id صریح',
        'SalonPaymentGateway' => 'همیشه از $salon->paymentGateways()',
        'SalonSmsUsage' => 'ابزار سهمیه‌ی خود سالن‌ها',
        'ShortLink' => 'لینک کوتاه پیامک؛ کد تصادفی به آدرسی که خود برنامه ساخته، بدون داده‌ی سالن',
        // از طریق متخصص به سالن وصل‌اند؛ فهرست/آمار با whereHas('specialist') و جزئیات با چک مالکیت
        'SpecialistWallet' => 'repository با whereHas(specialist)',
        'WithdrawalRequest' => 'repository با whereHas(specialist)',
        'Leave' => 'repository با whereHas(specialist)',
        'Holiday' => 'مختص متخصص؛ از رابطه‌ی متخصص',
        'SpecialistSchedule' => 'مختص متخصص؛ از رابطه‌ی متخصص',
        // از طریق نوبت / کاربر / کیف پول
        'Payment' => 'از رابطه‌ی نوبت',
        'LoyaltyPoint' => 'از رابطه‌ی کاربر/نوبت',
        'ReviewToken' => 'توکن یک‌بارمصرف نوبت',
        'DiscountUsage' => 'از رابطه‌ی کد تخفیف/نوبت',
        'WalletTransaction' => 'از رابطه‌ی کیف پول متخصص',
        'UserWallet' => 'کیف پول مشتری (مشتری مختص سالن است)',
        'UserWalletTransaction' => 'از رابطه‌ی کیف پول مشتری',
        'AdminWalletTransaction' => 'از رابطه‌ی کیف پول سالن',
        'UserNotification' => 'اعلان‌های خود کاربر؛ رابطه‌ی notifications() با UserNotification::limitToCurrentSalon() فیلتر می‌شود',
        'UserReportSetting' => 'تنظیم خود کاربر',
        'ScheduledReport' => 'مال کاربر',
        'ScheduledReportRun' => 'از رابطه‌ی گزارش زمان‌بندی‌شده',
        'SecurityLog' => 'از کاربر؛ بخش امنیت با querySalonMembers فیلتر می‌کند',
        'SupportTicket' => 'تیکت سالن به پلتفرم',
        'SupportTicketMessage' => 'از رابطه‌ی تیکت',
    ];

    private function models(): array
    {
        $models = [];
        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            if (class_exists($class) && is_subclass_of($class, Model::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                $models[class_basename($class)] = $class;
            }
        }

        return $models;
    }

    public function test_every_model_is_either_salon_scoped_or_listed_with_a_reason(): void
    {
        $unclassified = [];

        foreach ($this->models() as $name => $class) {
            $traits = class_uses_recursive($class);
            $scoped = isset($traits[BelongsToSalon::class]) || isset($traits[BelongsToSalonThroughSpecialist::class]);

            if ($scoped && isset(self::UNSCOPED[$name])) {
                $unclassified[] = "{$name}: scoped but also listed as unscoped — remove it from the list";
            } elseif (! $scoped && ! isset(self::UNSCOPED[$name])) {
                $table = (new $class)->getTable();
                $hint = Schema::hasColumn($table, 'salon_id') ? 'has salon_id' : 'no salon_id';
                $unclassified[] = "{$name} ({$table}, {$hint}): add BelongsToSalon / BelongsToSalonThroughSpecialist or list it with a reason";
            }
        }

        $this->assertSame([], $unclassified);
    }

    public function test_every_scoped_model_has_the_column_its_scope_needs(): void
    {
        foreach ($this->models() as $name => $class) {
            $traits = class_uses_recursive($class);
            $table = (new $class)->getTable();

            if (isset($traits[BelongsToSalon::class])) {
                $this->assertTrue(Schema::hasColumn($table, 'salon_id'), "{$name}: BelongsToSalon without {$table}.salon_id");
            }
            if (isset($traits[BelongsToSalonThroughSpecialist::class])) {
                $this->assertTrue(Schema::hasColumn($table, 'specialist_id'), "{$name}: through-specialist scope without {$table}.specialist_id");
            }
        }
    }

    public function test_the_list_names_only_models_that_exist(): void
    {
        $this->assertSame([], array_values(array_diff(array_keys(self::UNSCOPED), array_keys($this->models()))));
    }
}
