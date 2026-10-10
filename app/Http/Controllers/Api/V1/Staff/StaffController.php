<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use Illuminate\Http\Request;

/**
 * پایه‌ی کنترلرهای /api/v1/staff/* (پشت api.audience:staff): متخصص و سالن را EnsureApiAudience از توکن تعیین کرده
 * است؛ هیچ ورودی کاربر متخصص یا سالن را انتخاب نمی‌کند.
 */
abstract class StaffController extends Controller
{
    protected function specialist(Request $request): Specialist
    {
        return $request->attributes->get('api_specialist');
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    protected function pageMeta(\Illuminate\Contracts\Pagination\LengthAwarePaginator $page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ];
    }
}
