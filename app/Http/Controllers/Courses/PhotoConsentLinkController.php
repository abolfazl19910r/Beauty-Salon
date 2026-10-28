<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Services\Courses\CourseException;
use App\Services\Courses\PhotoConsentService;
use App\Support\Courses\PhotoConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * لینک پیامکی درخواست رضایت عکس (۲۰۲۶-۱۰-۲۸) برای مشتری‌ای که اپ ندارد یا واردنشده است: بی ورود؛ توکن یک‌بارمصرف (فقط هش در جدول)،
 * ۷ روز اعتبار، فقط در سالن خودش (scope سالن). متن کامل رضایت، نسخه و دو دکمه؛ موافقت با تیک «متن را خواندم».
 */
class PhotoConsentLinkController extends Controller
{
    public function __construct(private readonly PhotoConsentService $consents) {}

    public function show(string $token): View
    {
        $row = $this->consents->findByToken($token);
        abort_unless($row, 404);

        return view('courses.photo-consent.link', [
            'row' => $row,
            'course' => $row->customerCourse,
            'text' => PhotoConsent::TEXT,
            'version' => PhotoConsent::VERSION,
            'token' => $token,
        ]);
    }

    public function decide(Request $request, string $token): RedirectResponse
    {
        $row = $this->consents->findByToken($token);
        abort_unless($row, 404);

        $input = $request->validate(['decision' => ['required', 'in:agree,decline']]);
        $agree = $input['decision'] === 'agree';
        if ($agree) {
            $request->validate(['read_text' => ['accepted']], ['read_text.accepted' => 'برای موافقت، تیک «متن را خواندم» را بزنید.']);
        }

        try {
            $this->consents->decideByToken($row, $agree, $request);
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $agree
            ? 'موافقت شما ثبت شد. هر زمان می‌توانید آن را از «دوره‌های من» پس بگیرید.'
            : 'مخالفت شما ثبت شد؛ از این دوره عکسی گرفته نمی‌شود.');
    }
}
