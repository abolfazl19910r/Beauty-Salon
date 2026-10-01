<?php

namespace App\Traits;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesSpecialist
{
    protected function resolveSpecialist(bool $orFail = false): ?Specialist
    {
        // بدون سالن جاری یعنی EnsureSpecialistSalonActive متخصصی برای این کاربر پیدا نکرد (حالت سخت‌گیر ۲۰۲۶-۱۰-۰۱)
        $specialist = app(\App\Support\CurrentSalon::class)->id() !== null ? auth()->user()?->specialist : null;

        if (! $specialist && $orFail) {
            abort(404, 'رکورد متخصص برای این حساب کاربری یافت نشد.');
        }

        return $specialist;
    }

    protected function requireSpecialist(): Specialist
    {
        return $this->resolveSpecialist(orFail: true);
    }

    protected function resolveSpecialistOrFail(): Specialist
    {
        $specialist = auth()->user()?->specialist;

        if (! $specialist) {
            throw (new ModelNotFoundException)->setModel(Specialist::class);
        }

        return $specialist;
    }
}
