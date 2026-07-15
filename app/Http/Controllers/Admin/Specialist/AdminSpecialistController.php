<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Specialist\StoreSpecialistRequest;
use App\Http\Requests\Admin\Specialist\UpdateSpecialistRequest;
use App\Models\Category;
use App\Models\Specialist;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSpecialistController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $specialists = Specialist::whereNull('deleted_at')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['bookings' => function ($q) {
                $q->whereDate('booking_time', today());
            }])
            ->with('services:id,name')
            ->latest()
            ->paginate(10);

        return view('admin.specialists.index', compact('specialists'));
    }

    public function show(Specialist $specialist)
    {
        return view('admin.specialists.show', compact('specialist'));
    }

    public function create()
    {
        $services = Category::with('services')->get();

        return view('admin.specialists.create', compact('services'));
    }

    public function store(StoreSpecialistRequest $request)
    {
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($validated, $request) {
                $services = $validated['services'];
                unset($validated['services']);

                $validated['phone'] = $this->normalizePhone($validated['phone']);

                $validated['commission_rate'] = $request->input('commission_rate') !== ''
                    ? (float) $request->input('commission_rate')
                    : null;

                $matchedUser = User::where('phone', $validated['phone'])->first();
                $validated['user_id'] = $matchedUser?->id;

                $specialist = Specialist::create($validated);
                $specialist->services()->attach($services);

                $message = 'متخصص جدید با موفقیت ایجاد شد.';
                if (!$matchedUser) {
                    $message .= ' توجه: هنوز هیچ کاربری با این شماره موبایل ثبت‌نام نکرده — پس از ثبت‌نام متخصص با این شماره، پنل او فعال خواهد شد.';
                }

                return redirect()
                    ->route('admin.specialists.index')
                    ->with('success', $message);
            });
        } catch (\Exception $e) {
            Log::error('Error storing specialist: ' . $e->getMessage());
            return back()->with('error', 'خطایی در ثبت اطلاعات رخ داد.')->withInput();
        }
    }

    public function edit(Specialist $specialist)
    {
        $services = Category::with('services')->get();
        $selectedServices = $specialist->services->pluck('id')->toArray();
        return view('admin.specialists.edit', compact('specialist', 'services', 'selectedServices'));
    }

    public function update(UpdateSpecialistRequest $request, Specialist $specialist)
    {
        $validated = $request->validated();

        $services = $validated['services'];
        unset($validated['services']);

        $validated['phone'] = $this->normalizePhone($validated['phone']);

        $validated['commission_rate'] = $request->input('commission_rate') !== ''
            ? (float) $request->input('commission_rate')
            : null;

        $matchedUser = User::where('phone', $validated['phone'])->first();
        $validated['user_id'] = $matchedUser?->id;

        $specialist->update($validated);
        $specialist->services()->sync($services);

        return redirect()->route('admin.specialists.index')
            ->with('success', 'اطلاعات متخصص با موفقیت بروزرسانی شد.');
    }

    public function destroy(Specialist $specialist)
    {
        try {
            DB::beginTransaction();

            $specialist->services()->detach();
            $specialist->delete();

            DB::commit();

            return redirect()->route('admin.specialists.index')
                ->with('success', 'متخصص با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in destroy method: ' . $e->getMessage());

            return redirect()->route('admin.specialists.index')
                ->with('error', 'خطا در حذف متخصص');
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0098') && strlen($digits) === 14) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }
}
