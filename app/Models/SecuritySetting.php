<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;

class SecuritySetting extends Model
{
    use BelongsToSalon;

    protected $fillable = [
        'salon_id',
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
        return self::first() ?? self::create([])->fresh();
    }
}
