<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\Invoice;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⭐ «جستجوی یک شماره در همه‌ی سالن‌ها» (۲۰۲۶-۱۰-۱۵) برای پشتیبانی: کاربر (مالک/کادر/مشتری)، متخصص و درخواست دمو با آن شماره،
 * هر کدام با سالنش. دست‌کم ۴ رقم؛ ارقام فارسی و +98 پذیرفته می‌شوند.
 *
 * بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): جستجوی سراسری نوار بالای پنل همین‌جا می‌آید — به‌علاوه‌ی سالن (همان جستجوی فهرست سالن‌ها:
 * نام، آدرس اختصاصی، تلفن، نشانی، نام/موبایل مالک) و فاکتور (شماره‌ی فاکتور یا کد پیگیری درگاه).
 */
class PhoneSearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q'));
        $query = self::normalize($term);
        // فقط رقم (و + و فاصله و خط تیره): جستجوی شماره/فاکتور؛ وگرنه فقط سالن
        $numeric = $term !== '' && preg_match('/^[\s\d۰-۹٠-٩+\-#]+$/u', $term) === 1;
        $results = ['salons' => collect(), 'users' => collect(), 'specialists' => collect(), 'demos' => collect(), 'invoices' => collect()];

        if (! $numeric && mb_strlen($term) >= 2) {
            $results['salons'] = app(\App\Services\SuperAdmin\SalonListFilter::class)
                ->query(['q' => mb_substr($term, 0, 100), 'sort' => 'name'])->limit(20)->get();
        }

        if ($numeric && strlen($query) >= 4) {
            $like = '%'.$query.'%';
            $results['users'] = User::query()->where('phone', 'like', $like)
                ->with(['salons' => fn ($q) => $q->withoutGlobalScopes(), 'roles'])->orderBy('user_type')->limit(50)->get();
            $results['specialists'] = Specialist::withoutGlobalScopes()->withTrashed()->where('phone', 'like', $like)->limit(50)->get();
            $results['demos'] = DemoRequest::query()->where('phone', 'like', $like)->latest()->limit(20)->get();
            $results['salons'] = app(\App\Services\SuperAdmin\SalonListFilter::class)
                ->query(['q' => $query, 'sort' => 'name'])->limit(20)->get();
        }

        if ($numeric && $query !== '') {
            // شماره‌ی فاکتور دقیق، یا بخشی از کد پیگیری (دست‌کم ۴ رقم)
            $results['invoices'] = Invoice::withoutGlobalScopes()->with(['salon' => fn ($q) => $q->withoutGlobalScopes()])
                ->where(function ($q) use ($query) {
                    $q->where('id', (int) $query);
                    if (strlen($query) >= 4) {
                        $q->orWhere('ref_id', 'like', '%'.$query.'%');
                    }
                })->latest('id')->limit(20)->get();
        }

        $salonIds = $results['users']->pluck('salon_id')->merge($results['specialists']->pluck('salon_id'))->filter()->unique();

        return view('superadmin.search.index', [
            'q' => $request->query('q'),
            'query' => $query,
            'numeric' => $numeric,
            'results' => $results,
            'salons' => Salon::withoutGlobalScopes()->whereIn('id', $salonIds)->pluck('name', 'id'),
        ]);
    }

    /** ۰۹۱۲… / +989… / 9123… → رقم‌های لاتین؛ پیشوند 98 به 0 */
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D/', '', strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ])) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        return strlen($digits) === 10 && str_starts_with($digits, '9') ? '0'.$digits : $digits;
    }
}
