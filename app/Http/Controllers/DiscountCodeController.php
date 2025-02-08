<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Http\Resources\DiscountCodeResource;
use App\Services\DiscountCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DiscountCodeController extends Controller
{
    protected DiscountCodeService $discountCodeService;

    public function __construct(DiscountCodeService $discountCodeService)
    {
        $this->discountCodeService = $discountCodeService;
    }

    public function index()
    {
        $discountCodes = DiscountCode::where(function($query) {
            $query->where('user_id', auth()->id())
                ->orWhereNull('user_id');
        })
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->paginate(15);

        return view('loyalty.discounts.index', compact('discountCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:discount_codes,code',
            'type' => 'required|in:fixed,percentage',
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'percentage' && $value > 100) {
                        $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                    }
                }
            ],
            'max_uses' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
            'user_id' => 'nullable|exists:users,id'
        ]);

        try {
            $discountCode = $this->discountCodeService->create($validated);

            return redirect()->route('discount-codes.index')
                ->with('success', 'کد تخفیف با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'خطا در ایجاد کد تخفیف.');
        }
    }

    public function validate(Request $request)
    {
        $code = Cache::remember("discount_code_{$request->code}", 60, function() use ($request) {
            return DiscountCode::where('code', $request->code)->first();
        });

        if (!$code || !$code->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'کد تخفیف نامعتبر است.'
            ]);
        }

        return response()->json([
            'valid' => true,
            'discount' => [
                'type' => $code->type,
                'amount' => $code->amount,
                'max_amount' => $code->max_amount ?? null,
                'expires_at' => $code->expires_at?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    public function update(Request $request, DiscountCode $discountCode)
    {
        $validated = $request->validate([
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
            'max_uses' => 'sometimes|integer|min:' . $discountCode->used_count
        ]);

        try {
            $this->discountCodeService->update($discountCode, $validated);

            return redirect()->route('discount-codes.index')
                ->with('success', 'کد تخفیف با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'خطا در بروزرسانی کد تخفیف.');
        }
    }

    public function destroy(DiscountCode $discountCode)
    {
        if ($discountCode->used_count > 0) {
            return back()->with('error', 'کد تخفیف استفاده شده قابل حذف نیست.');
        }

        $discountCode->delete();

        return redirect()->route('discount-codes.index')
            ->with('success', 'کد تخفیف با موفقیت حذف شد.');
    }

    public function applyToBooking(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'booking_id' => 'required|exists:bookings,id'
        ]);

        try {
            $result = $this->discountCodeService->applyToBooking(
                $request->code,
                $request->booking_id
            );

            return response()->json([
                'success' => true,
                'discount_amount' => $result['discount_amount'],
                'final_price' => $result['final_price']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
