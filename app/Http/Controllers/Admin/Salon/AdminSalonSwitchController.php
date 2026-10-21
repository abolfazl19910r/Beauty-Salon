<?php

namespace App\Http\Controllers\Admin\Salon;

use App\Http\Controllers\Controller;
use App\Support\AdminSalonSelector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «سالن‌های من» پنل مدیر (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): عضو چند سالن بعد از ورود یکی را انتخاب می‌کند و از هدر پنل جابه‌جا می‌شود.
 * بیرون از middleware ‏salon.active (سالن هنوز انتخاب نشده)؛ فقط سالن‌هایی که کاربر واقعاً عضو آن‌هاست.
 */
class AdminSalonSwitchController extends Controller
{
    public function __construct(private readonly AdminSalonSelector $selector) {}

    public function choose(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $memberships = $this->selector->memberships($user);
        abort_if($memberships->isEmpty(), 403);

        if ($memberships->count() === 1 && ! $memberships->first()->is_suspended) {
            $this->selector->choose($user, $memberships->first()->id);

            return redirect()->route('admin.home');
        }

        return view('admin.salons.choose', [
            'memberships' => $memberships,
            'current' => $this->selector->chosenId(),
            'unread' => $this->selector->unreadCounts($user),
        ]);
    }

    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate(['salon_id' => ['required', 'integer']]);
        $salon = $this->selector->choose($request->user(), (int) $data['salon_id']);

        if ($salon === null) {
            return redirect()->route('admin.salons.choose')->withErrors(['salon' => 'این سالن در دسترس شما نیست.']);
        }

        return redirect()->route('admin.home')->with('success', "پنل سالن «{$salon->name}»");
    }
}
