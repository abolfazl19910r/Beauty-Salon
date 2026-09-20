<?php

namespace App\Http\Controllers\Admin\Loyalty;

use App\Http\Controllers\Controller;
use App\Services\Admin\Loyalty\LoyaltyAdminService;
use Illuminate\View\View;

class AdminLoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyAdminService $loyaltyAdminService,
    ) {}

    public function index(): View
    {
        return view('admin.loyalty.index', $this->loyaltyAdminService->getDashboardStats());
    }
}
