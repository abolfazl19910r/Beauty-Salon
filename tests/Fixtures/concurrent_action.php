<?php

/**
 * پردازه‌ی دوم تست‌های هم‌زمانی (Tests\Concerns\RunsConcurrentProcess): یک درخواست دیگر در پردازه و اتصال دیتابیس جدا،
 * مثل دومین درخواست HTTP هم‌زمان. خروجی یک کلمه است (created، quota، ...) یا error:<کلاس>: <پیام>.
 *
 * php tests/Fixtures/concurrent_action.php <action> <arg...>
 */

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\WithdrawalRequest;
use App\Support\CurrentSalon;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$action = $argv[1];
$args = array_slice($argv, 2);
$inSalon = fn (int $salonId) => $app->make(CurrentSalon::class)->set(Salon::findOrFail($salonId));

try {
    switch ($action) {
        case 'create-specialist': // salon_id phone email service_id
            [$salonId, $phone, $email, $serviceId] = $args;
            $inSalon((int) $salonId);
            $app->make(App\Services\Admin\Specialist\AdminSpecialistService::class)->create(
                ['name' => 'متخصص هم‌زمان', 'phone' => $phone, 'email' => $email, 'services' => [(int) $serviceId]],
                null,
            );
            echo 'created';
            break;

        case 'update-salon-limit': // salon_id max (سوپرادمین: بدون سالن جاری)
            [$salonId, $max] = $args;
            $salon = Salon::findOrFail((int) $salonId);
            $app->make(App\Services\SuperAdmin\SuperAdminService::class)->updateSalon($salon, [
                'name' => $salon->name, 'max_specialists_count' => (int) $max, 'module_permissions' => $salon->module_permissions,
            ]);
            echo 'updated';
            break;

        case 'create-withdrawal': // specialist_id amount [idempotency_key]
            $specialist = Specialist::withoutGlobalScopes()->findOrFail((int) $args[0]);
            $inSalon($specialist->salon_id);
            $data = ['amount' => $args[1], 'method' => 'bank_transfer'];
            if (isset($args[2])) {
                $data['idempotency_key'] = $args[2];
            }
            $result = $app->make(App\Services\Specialist\SpecialistWalletService::class)->createWithdrawal($specialist, $data);
            echo $result['success'] ? 'created:'.$result['withdrawal_request']->id : 'rejected';
            break;

        case 'cancel-withdrawal': // specialist_id withdrawal_id
            $specialist = Specialist::withoutGlobalScopes()->findOrFail((int) $args[0]);
            $inSalon($specialist->salon_id);
            $withdrawal = WithdrawalRequest::findOrFail((int) $args[1]);
            $cancelled = $app->make(App\Services\Specialist\SpecialistWalletService::class)->cancelWithdrawal($specialist, $withdrawal);
            echo $cancelled === false ? 'not-cancelled' : 'cancelled';
            break;

        default:
            echo 'error:unknown action '.$action;
    }
} catch (App\Exceptions\SpecialistQuotaExceededException) {
    echo 'quota';
} catch (InvalidArgumentException $e) {
    echo 'invalid';
} catch (Throwable $e) {
    echo 'error:'.get_class($e).': '.$e->getMessage();
}
