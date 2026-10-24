<?php

namespace Tests\Feature\Seeders;

use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * داده‌ی نمونه‌ی Docker (MAHRU_SEED=true، ۲۰۲۶-۱۰-۲۴): مشتری نمونه‌ی 09111111111 بی salon_id ساخته می‌شد و روی صفحه‌ی ورود سالن
 * «اطلاعات وارد شده صحیح نمی‌باشد» می‌گرفت.
 */
class DemoCustomerSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_customer_belongs_to_the_seeded_salon_and_can_start_login_there(): void
    {
        $salon = Salon::query()->where('id', app(CurrentSalon::class)->id())->firstOrFail();
        $this->seed([RoleSeeder::class, UserSeeder::class]);

        $customer = User::withoutGlobalScopes()->where('phone', '09111111111')->firstOrFail();
        $this->assertSame('customer', $customer->user_type);
        $this->assertSame($salon->id, (int) $customer->salon_id);

        $this->post('/s/'.$salon->slug.'/login', [
            'phone' => '09111111111', 'password' => 'password',
        ])->assertSessionHasNoErrors();
    }
}
