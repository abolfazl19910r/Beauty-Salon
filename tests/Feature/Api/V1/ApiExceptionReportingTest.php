<?php

namespace Tests\Feature\Api\V1;

use App\Exceptions\Api\ApiException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * خطای قابل‌انتظار API در laravel.log نمی‌آید؛ ۵xx می‌آید (گزارش ۲۰۲۶-۱۰-۱۸).
 */
class ApiExceptionReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_password_is_answered_but_not_logged(): void
    {
        Log::spy();
        Log::shouldReceive('channel')->andReturnSelf();   // لاگ امنیتی ورود ناموفق

        $this->postJson('/api/v1/staff/login', ['phone' => '09120000000', 'password' => 'wrong-pass'])
            ->assertStatus(422)->assertJsonPath('success', false);

        Log::shouldNotHaveReceived('error');
    }

    public function test_server_side_api_errors_are_still_logged(): void
    {
        Log::spy();

        app(ExceptionHandler::class)->report(new ApiException('gateway_unavailable', 'درگاه در دسترس نیست.', 503));
        app(ExceptionHandler::class)->report(new ApiException('not_found', 'یافت نشد.', 404));

        Log::shouldHaveReceived('error')->once();
    }
}
