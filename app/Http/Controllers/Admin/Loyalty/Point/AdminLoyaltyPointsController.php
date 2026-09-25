<?php

namespace App\Http\Controllers\Admin\Loyalty\Point;

use App\Exceptions\InsufficientLoyaltyPointsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Loyalty\Point\AddUserPointsRequest;
use App\Http\Requests\Admin\Loyalty\Point\DeductUserPointsRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Admin\Loyalty\LoyaltyAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLoyaltyPointsController extends Controller
{
    public function __construct(
        private readonly LoyaltyAdminService $loyaltyAdminService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $users = collect();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $users = $this->userRepository->querySalonCustomers()
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        $selectedUser = null;
        $pointsData = null;

        if ($request->filled('user_id')) {
            $selectedUser = $this->userRepository->querySalonCustomers()->find($request->integer('user_id'));

            if ($selectedUser) {
                $pointsData = $this->loyaltyAdminService->getUserPoints($selectedUser);
                $pointsData['history']->appends($request->query());
            }
        }

        return view('admin.loyalty.points.index', compact('users', 'selectedUser', 'pointsData'));
    }

    public function addPoints(AddUserPointsRequest $request, User $user): RedirectResponse
    {
        $this->ensureSalonCustomer($user);

        $this->loyaltyAdminService->addPoints(
            $user,
            (int) $request->validated('points'),
            $request->validated('description'),
            $request->validated('expires_at'),
        );

        return redirect()
            ->route('admin.loyalty.points.index', ['user_id' => $user->id])
            ->with('success', 'امتیاز با موفقیت به کاربر اضافه شد.');
    }

    public function deductPoints(DeductUserPointsRequest $request, User $user): RedirectResponse
    {
        $this->ensureSalonCustomer($user);

        try {
            $this->loyaltyAdminService->deductPoints(
                $user,
                (int) $request->validated('points'),
                $request->validated('description'),
            );

            return redirect()
                ->route('admin.loyalty.points.index', ['user_id' => $user->id])
                ->with('success', 'امتیاز با موفقیت از کاربر کسر شد.');
        } catch (InsufficientLoyaltyPointsException $e) {
            return redirect()
                ->route('admin.loyalty.points.index', ['user_id' => $user->id])
                ->with('error', $e->getUserMessage());
        }
    }

    /**
     * route model binding قبل از ست‌شدن سالن اجرا می‌شه، پس مشتری‌بودن کاربر در همین سالن اینجا چک می‌شه.
     */
    private function ensureSalonCustomer(User $user): void
    {
        abort_unless($this->userRepository->querySalonCustomers()->whereKey($user->id)->exists(), 404);
    }
}
