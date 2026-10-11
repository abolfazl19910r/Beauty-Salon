<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * پایه‌ی کنترلرهای /api/v1/customer/* (پشت api.audience:customer؛ بسته‌ی ۳): مشتری و سالن را EnsureApiAudience از
 * توکن تعیین کرده است (حساب مشتری در هر سالن جداست)؛ هیچ ورودی کاربر سالن را انتخاب نمی‌کند.
 */
abstract class CustomerController extends Controller
{
    protected function customer(Request $request): User
    {
        return $request->user();
    }

    protected function salon(): Salon
    {
        return app(CurrentSalon::class)->get();
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    protected function pageMeta(LengthAwarePaginator $page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ];
    }
}
