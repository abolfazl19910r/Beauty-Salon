<?php

namespace Tests\Feature\Http;

use Illuminate\Cache\RateLimiter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

/**
 * لاگ داکر ۲۰۲۶-۱۰-۲۹: «SQLSTATE[40001] … 1213 Deadlock … insert ignore into cache» از شمارنده‌ی throttle:api-v1 — کاربر ۵۰۰ می‌گرفت.
 * App\Http\Middleware\ThrottleRequests فقط شمارش را تکرار می‌کند، نه خود درخواست را.
 */
class ThrottleDeadlockRetryTest extends TestCase
{
    use RefreshDatabase;

    private int $handled = 0;

    private function deadlock(): QueryException
    {
        $pdo = new PDOException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction');
        $pdo->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock; try restarting transaction'];

        return new QueryException('mysql', 'insert ignore into `cache` …', [], $pdo);
    }

    private function limiterFailing(int $times): void
    {
        $failures = $times;
        $deadlock = $this->deadlock();
        $this->app->instance(RateLimiter::class, new class($this->app['cache']->driver(), $failures, $deadlock) extends RateLimiter
        {
            public function __construct($cache, private int $failures, private QueryException $deadlock)
            {
                parent::__construct($cache);
            }

            public function hit($key, $decaySeconds = 60)
            {
                if ($this->failures-- > 0) {
                    throw $this->deadlock;
                }

                return parent::hit($key, $decaySeconds);
            }
        });
    }

    private function route(): string
    {
        Route::middleware('throttle:5,1')->get('/__throttle-probe', function () {
            $this->handled++;

            return response('ok');
        });

        return '/__throttle-probe';
    }

    public function test_a_deadlocked_counter_is_retried_and_the_request_runs_once(): void
    {
        $this->limiterFailing(1);
        $this->get($this->route())->assertOk()->assertSee('ok');
        $this->assertSame(1, $this->handled);
    }

    public function test_it_gives_up_after_three_tries(): void
    {
        $this->limiterFailing(3);
        $this->withoutExceptionHandling();
        $this->expectException(QueryException::class);
        $this->get($this->route());
    }

    public function test_an_error_inside_the_request_is_never_retried(): void
    {
        Route::middleware('throttle:5,1')->get('/__throttle-probe-fail', function () {
            $this->handled++;
            throw $this->deadlock();
        });

        $this->get('/__throttle-probe-fail')->assertStatus(500);
        $this->assertSame(1, $this->handled, 'درخواست دو بار اجرا نشد');
    }
}
