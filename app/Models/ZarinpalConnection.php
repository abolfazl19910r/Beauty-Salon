<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⭐ «اتصال حساب زرین‌پال» یک سالن (بسته‌ی 5ز، ۲۰۲۶-۱۰-۳۰) — App\Services\Payment\Zarinpal\ZarinpalConnectService.
 * مثل SalonPaymentGateway عمداً BelongsToSalon ندارد: همیشه از $salon->zarinpalConnection() خوانده می‌شود.
 */
class ZarinpalConnection extends Model
{
    protected $fillable = ['salon_id', 'connected_by', 'access_token', 'token_expires_at', 'terminal_id', 'terminals', 'requested_terminal', 'synced_at'];

    protected $hidden = ['access_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'terminals' => 'array',
        'requested_terminal' => 'array',
        'synced_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function tokenUsable(): bool
    {
        return filled($this->access_token) && ($this->token_expires_at === null || $this->token_expires_at->isFuture());
    }
}
