<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Exceptions\SpecialistQuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Specialist\StoreSpecialistRequest;
use App\Http\Requests\Admin\Specialist\UpdateSpecialistRequest;
use App\Models\Specialist;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Admin\Specialist\AdminSpecialistService;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminSpecialistController extends Controller
{
    public function __construct(
        protected readonly CategoryService $categoryService,
        protected readonly AdminSpecialistService $specialistService,
        protected readonly CategoryRepositoryInterface $categoryRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->filled('search') ? $request->search : null,
        ];

        $specialists = $this->specialistRepository->paginateWithFilters($filters, 10);

        return view('admin.specialists.index', compact('specialists'));
    }

    public function show(Specialist $specialist): View
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        return view('admin.specialists.show', compact('specialist'));
    }

    public function create(): View
    {
        $services = $this->categoryRepository->getWithServices();

        return view('admin.specialists.create', compact('services'));
    }

    public function store(StoreSpecialistRequest $request): RedirectResponse
    {
        try {
            $result = $this->specialistService->create(
                $request->validated(),
                $request->input('commission_rate')
            );

            $message = 'متخصص جدید با موفقیت ایجاد شد.';
            if (! $result['matched_user']) {
                $message .= ' توجه: هنوز هیچ کاربری با این شماره موبایل ثبت‌نام نکرده — پس از ثبت‌نام متخصص با این شماره، پنل او فعال خواهد شد.';
            }

            return redirect()
                ->route('admin.specialists.index')
                ->with('success', $message);
        } catch (SpecialistQuotaExceededException $e) {
            return back()->withInput()->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            Log::error('Error storing specialist: '.$e->getMessage());

            return back()->with('error', 'خطایی در ثبت اطلاعات رخ داد.')->withInput();
        }
    }

    public function edit(Specialist $specialist): View
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $services = $this->categoryRepository->getWithServices();
        $selectedServices = $specialist->services->pluck('id')->toArray();

        return view('admin.specialists.edit', compact('specialist', 'services', 'selectedServices'));
    }

    public function update(UpdateSpecialistRequest $request, Specialist $specialist): RedirectResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $this->specialistService->update(
            $specialist,
            $request->validated(),
            $request->input('commission_rate')
        );

        return redirect()->route('admin.specialists.index')
            ->with('success', 'اطلاعات متخصص با موفقیت بروزرسانی شد.');
    }

    public function destroy(Specialist $specialist): RedirectResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        try {
            $this->specialistService->delete($specialist);

            return redirect()->route('admin.specialists.index')
                ->with('success', 'متخصص با موفقیت حذف شد.');

        } catch (\Exception $e) {
            Log::error('Error in destroy method: '.$e->getMessage());

            return redirect()->route('admin.specialists.index')
                ->with('error', 'خطا در حذف متخصص');
        }
    }
}
