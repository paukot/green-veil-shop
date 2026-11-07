<?php

namespace App\Http\Controllers;

use App\Actions\Auth\MigrateGuestSessionData;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function createLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function storeLogin(
        LoginRequest $request,
        MigrateGuestSessionData $migrateGuestData
    ): RedirectResponse {
        $guestCart = $request->session()->get('cart', []);
        $guestFavourites = $request->session()->get('favourites', []);

        $request->authenticate();
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        $migrateGuestData->execute($user, $guestCart, $guestFavourites, $request);

        return redirect()->intended(route('home'))->with('success', 'Welcome back to Green Veil!');
    }

    /**
     * Display the registration view.
     */
    public function createRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function storeRegister(
        RegisterRequest $request,
        MigrateGuestSessionData $migrateGuestData
    ): RedirectResponse {
        $guestCart = $request->session()->get('cart', []);
        $guestFavourites = $request->session()->get('favourites', []);

        $user = User::create([
            'name' => $request->string('name')->trim()->value(),
            'email' => $request->string('email')->trim()->lower()->value(),
            'password' => Hash::make($request->string('password')->value()),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $migrateGuestData->execute($user, $guestCart, $guestFavourites, $request);

        return redirect()->intended(route('home'))->with('success', 'Account created successfully! Welcome to Green Veil.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }
}
