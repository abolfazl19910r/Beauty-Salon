<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySetting extends Model
{
    protected $fillable = [
        'password_expiry_days',
    ];

    protected $casts = [
        'password_expiry_days' => 'integer',
    ];

    /**
     * Always returns a real instance (not a Collection), similar to WalletSetting::get().
     */
    public static function get(): self
    {
        return self::first() ?? self::create([]);
    }
}
