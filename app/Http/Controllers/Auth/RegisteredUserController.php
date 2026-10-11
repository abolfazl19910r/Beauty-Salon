<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class RegisteredUserController extends Controller
{
    /**
     * ⭐ Customer identity redesign (confirmed 2026-08-30): self-registration through this
     * controller was, in practice, always customer signup — nothing else in this codebase
     * creates an admin (App\Services\Admin\User\AdminUserService, via /admin/users) or specialist
     * (App\Services\Admin\Specialist\AdminSpecialistService, matched by phone) account through a
     * public registration form. Redirecting here rather than deleting the route keeps
     * route('register') working everywhere it's already referenced across the codebase — it now
     * just lands on the default salon's own registration page
     * (CustomerRegisteredController::create()) instead of rendering a form itself. The old store()/
     * verify()/resendCode() were removed on 2026-10-11: they created customer rows with no salon and
     * sent SMS codes from the central domain.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('salon.register', ['salon_slug' => 'rasta']);
    }
}
