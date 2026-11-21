<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\ClaimGuestOrders;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    /**
     * Ask the customer to confirm their email address.
     */
    public function notice(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('account.profile');
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $user->email,
        ]);
    }

    /**
     * Confirm the email address from the signed link, then claim guest orders placed with it.
     */
    public function verify(EmailVerificationRequest $request, ClaimGuestOrders $claimGuestOrders): RedirectResponse
    {
        $request->fulfill();

        /** @var User $user */
        $user = $request->user();

        $claimed = $claimGuestOrders->execute($user);

        $message = $claimed > 0
            ? "Email confirmed. We added {$claimed} earlier ".str('order')->plural($claimed).' to your account.'
            : 'Email confirmed. Thank you!';

        return redirect()->route('account.profile')->with('success', $message);
    }

    /**
     * Send a fresh verification link.
     */
    public function send(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('account.profile');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'A new verification link has been sent to your email address.');
    }
}
