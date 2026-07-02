<?php

namespace App\Traits;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesSpecialist
{
    protected function resolveSpecialist(): ?Specialist
    {
        return auth()->user()?->specialist;
    }

    protected function requireSpecialist(): Specialist
    {
        $specialist = auth()->user()?->specialist;

        if (! $specialist) {
            abort(404, 'رکورد متخصص برای این حساب کاربری یافت نشد.');
        }

        return $specialist;
    }

    protected function resolveSpecialistOrFail(): Specialist
    {
        $specialist = auth()->user()?->specialist;

        if (! $specialist) {
            throw (new ModelNotFoundException())->setModel(Specialist::class);
        }

        return $specialist;
    }
}
