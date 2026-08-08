<?php

namespace App\Http\Controllers\Admin\DiscountCode;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiscountCode\PreviewDiscountCodeRequest;
use App\Http\Requests\Admin\DiscountCode\StoreDiscountCodeRequest;
use App\Http\Requests\Admin\DiscountCode\UpdateDiscountCodeRequest;
use App\Models\DiscountCode;
use App\Models\User;
use App\Services\Admin\DiscountCode\AdminDiscountCodeService;
use App\Services\Discount\DiscountCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminDiscountCodeController extends Controller
{
    public function __construct(
        private readonly AdminDiscountCodeService $service,
    ) {}

    public function index(): View
    {
        return view('admin.discount-codes.index', [
            'discountCodes' => $this->service->paginate(),
            'stats' => $this->service->stats(),
        ]);
    }

    public function create(): View
    {
        return view('admin.discount-codes.create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(StoreDiscountCodeRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('admin.discount-codes.index')
            ->with('success', 'کد تخفیف با موفقیت ایجاد شد.');
    }

    /**
     * type/amount/max_amount aren't editable (see UpdateDiscountCodeRequest / AdminDiscountCodeService
     * doc-comments) so this preview is static/server-rendered — computed once here via
     * DiscountCalculator against a representative sample amount, not the interactive AJAX widget
     * the create page has.
     */
    public function edit(DiscountCode $discountCode, DiscountCalculator $calculator): View
    {
        $sampleAmount = 1_000_000;
        $preview = $calculator->calculate($discountCode, $sampleAmount);

        return view('admin.discount-codes.edit', [
            'discountCode' => $discountCode,
            'sampleAmount' => $sampleAmount,
            'preview' => $preview,
        ]);
    }

    public function update(UpdateDiscountCodeRequest $request, DiscountCode $discountCode): RedirectResponse
    {
        $this->service->update($discountCode, $request->validated());

        return redirect()->route('admin.discount-codes.index')
            ->with('success', 'کد تخفیف با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        try {
            $this->service->destroy($discountCode);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.discount-codes.index')
            ->with('success', 'کد تخفیف با موفقیت حذف شد.');
    }

    public function preview(PreviewDiscountCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json($this->service->preview(
            $validated['type'],
            (float) $validated['amount'],
            isset($validated['max_amount']) ? (float) $validated['max_amount'] : null,
            (float) $validated['base_amount'],
        ));
    }
}
