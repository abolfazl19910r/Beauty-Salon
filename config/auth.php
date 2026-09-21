<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    'verification_code_expire_minutes' => (int) (env('VERIFICATION_CODE_EXPIRE_MINUTES') ?: 2),

    /*
    |--------------------------------------------------------------------------
    | Login Throttling
    |--------------------------------------------------------------------------
    |
    | ⭐ Wired up (test-writing session 11): MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES
    | were .env.example-only placeholders — the 'auth' rate limiter in
    | RouteServiceProvider hardcoded Limit::perMinute(5) (5 attempts / 1 minute decay).
    | Same effective defaults preserved here.
    |
    */

    'max_login_attempts' => (int) (env('MAX_LOGIN_ATTEMPTS') ?: 5),

    'login_throttle_minutes' => (int) (env('LOGIN_THROTTLE_MINUTES') ?: 1),

    /*
    |--------------------------------------------------------------------------
    | Password Reset Code Expiry
    |--------------------------------------------------------------------------
    |
    | ⭐ Wired up (test-writing session 11): RESET_CODE_EXPIRE_MINUTES was another
    | .env.example-only placeholder — PasswordResetController::sendCode() hardcoded
    | now()->addMinutes(2). Same default (2) preserved.
    |
    */

    'reset_code_expire_minutes' => (int) (env('RESET_CODE_EXPIRE_MINUTES') ?: 2),

];
