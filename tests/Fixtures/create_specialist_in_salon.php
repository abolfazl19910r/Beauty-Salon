<?php

/**
 * پردازه‌ی دوم برای SpecialistQuotaConcurrencyTest: یک درخواست «ساخت متخصص» دیگر برای همان سالن، در پردازه و اتصال
 * دیتابیس جدا (مثل دومین درخواست HTTP هم‌زمان). خروجی: created | quota | error:<پیام>
 *
 * php tests/Fixtures/create_specialist_in_salon.php <salon_id> <phone> <email> <service_id>
 */

use App\Exceptions\SpecialistQuotaExceededException;
use App\Models\Salon;
use App\Services\Admin\Specialist\AdminSpecialistService;
use App\Support\CurrentSalon;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[, $salonId, $phone, $email, $serviceId] = $argv;

$app->make(CurrentSalon::class)->set(Salon::findOrFail((int) $salonId));

try {
    $app->make(AdminSpecialistService::class)->create(
        ['name' => 'متخصص هم‌زمان', 'phone' => $phone, 'email' => $email, 'services' => [(int) $serviceId]],
        null,
    );
    echo 'created';
} catch (SpecialistQuotaExceededException) {
    echo 'quota';
} catch (Throwable $e) {
    echo 'error:'.get_class($e).': '.$e->getMessage();
}
