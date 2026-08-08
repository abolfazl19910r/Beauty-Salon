<?php

namespace App\Traits;

use Closure;
use Exception;
use Illuminate\Support\Facades\DB;

trait DatabaseTransaction
{
    /**
     * @throws Exception
     */
    protected function executeInTransaction(Closure $callback, string $errorMessage = 'خطا در اجرای عملیات', int $attempts = 3): mixed
    {
        $attempt = 1;

        while ($attempt <= $attempts) {
            try {
                return DB::transaction(function () use ($callback) {
                    return $callback();
                });
            } catch (Exception $e) {
                if ($this->shouldRetryTransaction($e) && $attempt < $attempts) {
                    $backoff = 100 * pow(2, $attempt);
                    usleep($backoff * 1000);

                    $attempt++;

                    continue;
                }

                throw new Exception($errorMessage.': '.$e->getMessage(), 0, $e);
            }
        }
    }

    protected function shouldRetryTransaction(Exception $e): bool
    {
        $retryableErrors = [
            'Deadlock found',
            'Lock wait timeout',
            'could not obtain lock',
            'database is locked',
            'SQLSTATE[40001]',
            'SQLSTATE[HY000]',
            'General error: 1205',
        ];

        $errorMessage = $e->getMessage();

        foreach ($retryableErrors as $error) {
            if (stripos($errorMessage, $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
