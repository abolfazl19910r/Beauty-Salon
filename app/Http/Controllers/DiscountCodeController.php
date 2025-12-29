<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Booking;
use App\Http\Resources\DiscountCodeResource;
use App\Services\DiscountCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    public function applyToBooking(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string',
                'service_id' => 'required|exists:beauty_services,id'
            ]);

            $discountCode = DiscountCode::where('code', $validated['code'])->first();

            if (!$discountCode || !$discountCode->isValid()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'کد تخفیف نامعتبر است یا منقضی شده.'
                ], 200);
            }

            if ($discountCode->user_id && $discountCode->user_id !== auth()->id()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'این کد تخفیف برای شما قابل استفاده نیست.'
                ], 200);
            }

            $prepaymentAmount = 50000;

            $discountAmount = $discountCode->type === 'percentage'
                ? ($prepaymentAmount * $discountCode->amount / 100)
                : $discountCode->amount;

            if ($discountCode->max_amount) {
                $discountAmount = min($discountAmount, $discountCode->max_amount);
            }

            $finalPrice = max(0, $prepaymentAmount - $discountAmount);

            return response()->json([
                'valid' => true,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'message' => 'کد تخفیف معتبر است و آماده اعمال می‌باشد.'
            ]);

        } catch (\Exception $e) {
            Log::error('خطا در بررسی کد تخفیف', [
                'error' => $e->getMessage(),
                'code' => $request->code ?? null
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'خطا در بررسی کد تخفیف.'
            ], 500);
        }
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
}
