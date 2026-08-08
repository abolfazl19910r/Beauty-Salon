<?php

use App\Exceptions\DomainException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'check.booking.ownership' => \App\Http\Middleware\CheckBookingOwnership::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            '2fa.enabled' => \App\Http\Middleware\EnsureTwoFactorVerifiedForPayment::class,
        ]);

        $middleware->group('admin', [
            'auth',
            'admin',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('wallet:settle-pending')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer();
        $schedule->command('review-tokens:cleanup')
            ->daily();
        // R-Events: already via event(new ReminderScheduleEvent()) +
        // A listener that sets a config flag + a provider that sets that flag
        // It was checking and it was scheduled. Because that provider (EventServiceProvider) never
        // was not registered in bootstrap/providers.php, this whole chain from day one
        // was ineffective and the queue reminder was never automatically sent. replaced with
        // Direct settlement, like the wallet:settle-pending pattern above.
        // Update (2026-07-25): Changed from dailyAt('18:00') (once a day, for all
        // shifts in the next 24 hours) to every 10 minutes to align with the new logic of the command
        // (55-65 minute window before each shift) — meaning each shift will get a reminder exactly
        // about 1 hour before, not just once a day for all shifts tomorrow.
        $schedule->command('bookings:send-reminders')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->onOneServer();
        $schedule->command('reports:cleanup-exports')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (DomainException $e, Request $request) {
            $context = $e->context();
            if (! empty($context)) {
                Log::warning(get_class($e), array_merge($context, [
                    'message' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'user_id' => $request->user()?->id,
                ]));
            }

            $payload = [
                'success' => false,
                'message' => $e->getUserMessage(),
                'type' => class_basename($e),
            ];

            if ($request->expectsJson()) {
                return response()->json($payload, $e->getHttpStatus());
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', $e->getUserMessage());
        });

        $exceptions->renderable(function (HttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('http-statuses.'.$e->getStatusCode()),
                    'status' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });
    })
    ->create();
