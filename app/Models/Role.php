<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
    ];

    /**
     * کاربرانی که این نقش را دارند
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * اضافه کردن یک نقش به کاربر
     */
    public function assignToUser(User $user)
    {
        return $this->users()->attach($user);
    }

    /**
     * حذف یک نقش از کاربر
     */
    public function removeFromUser(User $user)
    {
        return $this->users()->detach($user);
    }
}
