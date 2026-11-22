<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticateUserAction $authenticateUser): RedirectResponse
    {
        $authenticateUser->execute($request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, LogoutUserAction $logoutUser): RedirectResponse
    {
        $logoutUser->execute($request);

        return redirect()->route('login');
    }
}
