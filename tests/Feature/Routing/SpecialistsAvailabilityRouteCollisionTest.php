<?php

namespace Tests\Feature\Routing;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * ⭐ Fix (روت هم‌URI تکراری، ۲۰۲۶-۰۹-۲۰): specialists.availability و specialists.available-slots
 * قبلاً هم در web/public-specialists.php (بدون auth) و هم در web/services.php (داخل گروه
 * ['auth', 'verified', 'salon.customer']) با دقیقاً همون method+URI ثبت می‌شدن.
 * Illuminate\Routing\RouteCollection::addToCollections() این‌ها رو با کلید "$method.$domainAndUri"
 * نگه می‌داره، پس ثبت دوم (services.php، چون بعد از public-specialists.php لود می‌شه) بی‌صدا
 * نسخه‌ی اول رو overwrite می‌کرد — یعنی نسخه‌ی "عمومی" هیچ‌وقت واقعاً reachable نبود، هرچند از
 * routes/web/public-specialists.php ثبت می‌شد و توی route:list هم دو بار (با همون یک URI) دیده
 * می‌شد. این تست دو چیز رو تضمین می‌کنه: (۱) هر URI فقط یک‌بار در جدول route ثبت شده، نه دوبار
 * (۲) رفتار واقعی auth-gated که از قبل توسط تست‌های دیگه (CrossSalonCustomerFacingImplicitBindingTest،
 * SpecialistControllerTest) فرض شده، واقعاً برقراره — یعنی کاربر مهمان به لاگین ریدایرکت می‌شه، نه
 * اینکه مستقیماً به نسخه‌ی عمومی (که پاک شده) برسه.
 */
class SpecialistsAvailabilityRouteCollisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialists_availability_uri_is_registered_exactly_once(): void
    {
        $matches = collect(RouteFacade::getRoutes())
            ->filter(fn ($route) => $route->uri() === 's/{salon_slug}/specialists/{specialist}/availability'
                && in_array('GET', $route->methods(), true));

        $this->assertCount(
            1,
            $matches,
            'انتظار می‌رفت specialists/{specialist}/availability فقط یک‌بار در جدول روت ثبت شده باشد، نه دوباره (یک‌بار عمومی، یک‌بار auth-gated).'
        );
    }

    public function test_specialists_available_slots_uri_is_registered_exactly_once(): void
    {
        $matches = collect(RouteFacade::getRoutes())
            ->filter(fn ($route) => $route->uri() === 's/{salon_slug}/specialists/{specialist}/available-slots/{date}'
                && in_array('GET', $route->methods(), true));

        $this->assertCount(
            1,
            $matches,
            'انتظار می‌رفت specialists/{specialist}/available-slots/{date} فقط یک‌بار در جدول روت ثبت شده باشد.'
        );
    }

    public function test_guest_hitting_specialist_availability_is_redirected_to_login_not_served_directly(): void
    {
        $specialist = Specialist::factory()->create();

        $this->get(route('specialists.availability', ['specialist' => $specialist->id]))
            ->assertRedirect(); // 'auth' middleware kicks in — never a direct 200 from the removed public copy
    }

    public function test_authenticated_customer_can_still_reach_specialist_availability(): void
    {
        $specialist = Specialist::factory()->create();
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->getJson(route('specialists.availability', ['specialist' => $specialist->id]))
            ->assertOk();
    }
}
