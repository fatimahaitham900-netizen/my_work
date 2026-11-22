<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthenticateUserAction
{
    /**
     * Validate credentials, verify account activity, and regenerate session.
     *
     * @throws ValidationException
     */
    public function execute(LoginRequest $request): void
    {
        $request->ensureIsNotRateLimited();

        $loginInput = trim($request->string('email')->value());
        $password = $request->string('password')->value();

        $user = User::query()
            ->where(function ($query) use ($loginInput): void {
                $query->where('email', $loginInput)
                    ->orWhere('name', $loginInput);
            })
            ->first();

        if ($user !== null && ! $user->is_active) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.inactive'),
            ]);
        }

        $attempted = false;
        if ($user !== null) {
            $attempted = Auth::attempt([
                'email' => $user->email,
                'password' => $password,
            ], $request->boolean('remember'));
        }

        if (! $attempted) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();
    }
}
