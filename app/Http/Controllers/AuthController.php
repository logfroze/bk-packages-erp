<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show login screen.
     * If device has a remembered user email, presents the Quick 4-Digit PIN screen.
     * Otherwise presents the Email & Password form.
     */
    public function showLogin(Request $request): View|RedirectResponse
    {
        // If already authenticated and pin is verified, go to dashboard
        if (Auth::check()) {
            $user = Auth::user();
            if (! $user->hasPin()) {
                return redirect()->route('pin.setup');
            }
            if (session('pin_unlocked', true)) {
                return redirect()->route('dashboard');
            }
        }

        $forcePassword = $request->query('mode') === 'password';
        $savedEmail = $request->cookie('bk_device_pin_email');

        $savedUser = null;
        if ($savedEmail && ! $forcePassword) {
            $savedUser = User::where('email', $savedEmail)->first();
        }

        return view('auth.login', compact('savedUser', 'forcePassword'));
    }

    /**
     * Authenticate via full Email & 20-character Password.
     * Keeps user logged in across devices (remember = true).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Always remember login session for continuous device access
        if (Auth::attempt($credentials, remember: true)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            // Store device email cookie for 10 years (5,256,000 minutes)
            Cookie::queue('bk_device_pin_email', $user->email, 5256000);

            // Check if user already has a PIN in the database
            if (! $user->hasPin()) {
                // First-time setup: prompt user to configure their 4-digit PIN
                session(['pin_unlocked' => false]);

                return redirect()->route('pin.setup');
            }

            // User already has a PIN configured: unlock and enter
            session(['pin_unlocked' => true]);

            Log::info('Security: User authenticated via password', ['email' => $user->email, 'ip' => $request->ip()]);

            if ($user->role === 'GATE_OPERATOR') {
                return redirect()->intended(route('gate.index'));
            }

            return redirect()->intended(route('dashboard'));
        }

        Log::warning('Security: Failed password login attempt', [
            'email' => $credentials['email'] ?? 'unknown',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()
            ->withErrors(['email' => 'Invalid email or password. Please re-check credentials.'])
            ->onlyInput('email');
    }

    /**
     * Authenticate or unlock via the 4-digit PIN code.
     */
    public function loginWithPin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'pin' => 'required|digits:4',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return redirect()->route('login', ['mode' => 'password'])
                ->withErrors(['email' => 'User profile not found. Please log in with email and password.']);
        }

        if (! $user->hasPin()) {
            // Need password login first to establish PIN
            return redirect()->route('login', ['mode' => 'password'])
                ->with('info', 'Please log in with your password first to configure your 4-digit PIN.');
        }

        if (! $user->verifyPin($validated['pin'])) {
            Log::warning('Security: Failed 4-digit PIN attempt', [
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return back()->withErrors(['pin' => 'Incorrect 4-digit PIN code. Please try again.']);
        }

        // Successfully verified PIN
        Auth::login($user, remember: true);
        $request->session()->regenerate();
        session(['pin_unlocked' => true]);

        $user->last_login_at = now();
        $user->save();

        Log::info('Security: User unlocked session via 4-digit PIN', ['email' => $user->email, 'ip' => $request->ip()]);

        Cookie::queue('bk_device_pin_email', $user->email, 5256000);

        if ($user->role === 'GATE_OPERATOR') {
            return redirect()->intended(route('gate.index'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show screen to configure the 4-digit PIN (first-time login).
     */
    public function showPinSetup(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if ($user->hasPin()) {
            return redirect()->route('dashboard');
        }

        return view('auth.setup_pin', compact('user'));
    }

    /**
     * Store the new 4-digit PIN.
     */
    public function storePin(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'pin' => 'required|digits:4',
            'pin_confirmation' => 'required|same:pin',
        ]);

        $user = Auth::user();
        $user->setPin($validated['pin']);
        session(['pin_unlocked' => true]);

        Cookie::queue('bk_device_pin_email', $user->email, 5256000);

        return redirect()->route('dashboard')->with('success', 'Security PIN configured successfully! You can now use this 4-digit PIN to quickly log in on any device.');
    }

    /**
     * Instant Lock: Locks the app on this device and displays PIN unlock screen.
     */
    public function lock(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            Cookie::queue('bk_device_pin_email', $user->email, 5256000);
        }
        session(['pin_unlocked' => false]);

        return redirect()->route('login');
    }

    /**
     * Switch Account: Clears remembered device email and shows standard login.
     */
    public function switchAccount(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cookie::queue(Cookie::forget('bk_device_pin_email'));

        return redirect()->route('login', ['mode' => 'password']);
    }

    /**
     * Intentional Sign Out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['mode' => 'password'])->with('info', 'You have been signed out.');
    }
}
