<?php

namespace App\Http\Controllers\Admin\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Wallet\UpdateWalletSettingsRequest;
use App\Models\WalletSetting;
use App\Services\Admin\Wallet\WalletAdminService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminWalletSettingsController extends Controller
{
    public function __construct(
        private readonly WalletAdminService $walletAdminService
    ) {
    }

    public function index(): View
    {
        $settings = WalletSetting::first();

        return view('admin.wallet.settings', compact('settings'));
    }

    public function update(UpdateWalletSettingsRequest $request): RedirectResponse
    {
        try {
            $this->walletAdminService->updateSettings($request->validated());

            return back()->with('success', 'تنظیمات با موفقیت به‌روزرسانی شد.');
        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی تنظیمات کیف پول', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در ذخیره تنظیمات: ' . $e->getMessage());
        }
    }
}
