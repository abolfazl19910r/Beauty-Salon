<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/** صفحه‌ی انصراف از پیامک‌های پیگیری سالن (لینک امضاشده‌ی پیامک، ۲۰۲۶-۱۰-۱۴) */
class SmsUnsubscribeController extends Controller
{
    public function show(int $customer): View
    {
        $customer = $this->customer($customer);

        return view('marketing.sms-unsubscribe', [
            'salon' => app(CurrentSalon::class)->get(),
            'done' => $customer->marketing_sms_opted_out_at !== null,
            'action' => URL::signedRoute('sms.unsubscribe.store', ['customer' => $customer->id]),
        ]);
    }

    public function store(Request $request, int $customer): View
    {
        $customer = $this->customer($customer);
        if ($customer->marketing_sms_opted_out_at === null) {
            $customer->forceFill(['marketing_sms_opted_out_at' => now()])->save();
        }

        return view('marketing.sms-unsubscribe', ['salon' => app(CurrentSalon::class)->get(), 'done' => true, 'action' => null]);
    }

    /** فقط مشتریِ همین سالن؛ امضا جلوی حدس شناسه را می‌گیرد، این شرط جلوی بردن لینک یک سالن به سالن دیگر را */
    private function customer(int $id): User
    {
        return User::query()
            ->whereKey($id)
            ->where('user_type', 'customer')
            ->where('salon_id', app(CurrentSalon::class)->id())
            ->firstOrFail();
    }
}
