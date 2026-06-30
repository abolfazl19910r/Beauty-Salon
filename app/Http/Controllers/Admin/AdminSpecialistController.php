<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\User;
use App\Services\CategoryService;
use App\Models\Category;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:11|unique:specialists,phone',
            'email'           => 'required|email|unique:specialists,email',
            'services'        => ['required', 'array'],
            'services.*'      => ['exists:beauty_services,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

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

    public function update(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:11|unique:specialists,phone,' . $specialist->id,
            'email'           => 'required|email|unique:specialists,email,' . $specialist->id,
            'services'        => ['required', 'array'],
            'services.*'      => ['exists:beauty_services,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

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
