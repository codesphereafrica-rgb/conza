<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VisitorLog;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (! $this->turnstileIsValid($request)) {
            return back()->withErrors(['captcha' => 'Vérification robot échouée.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                'confirmed',
            ],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'member',
            'status' => 'active',
            'avatar' => $request->hasFile('avatar')
                ? app(Cloudinary::class)->uploadApi()->upload($request->file('avatar')->getRealPath(), ['folder' => 'conza_avatars'])->offsetGet('secure_url')
                : null,
        ]);

        Auth::login($user);

        return redirect()->route('home')->with('success', 'Votre compte a bien été créé.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        if (! $this->turnstileIsValid($request)) {
            return back()->withErrors(['captcha' => 'Vérification robot échouée.']);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower(trim($credentials['email']));
        $ip = (string) $request->ip();
        $key = 'login-attempts:' . sha1($ip . '|' . $email);
        $blockedKey = 'login-blocked:' . sha1($ip . '|' . $email);

        if (Cache::has($blockedKey)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives, réessayez dans 24h.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($key, 10)) {
            Cache::put($blockedKey, true, now()->addHours(24));
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives, réessayez dans 24h.',
            ]);
        }

        if (! Auth::attempt($credentials)) {
            if (Schema::hasTable('login_attempts')) {
                DB::table('login_attempts')->insert([
                    'email' => $email,
                    'ip' => $ip,
                    'user_agent' => $request->userAgent(),
                    'success' => false,
                    'created_at' => now(),
                ]);
            }
            $this->syncVisitorLoginAttempts($ip);

            $attempts = RateLimiter::hit($key, 60 * 60 * 24);
            if ($attempts >= 10) {
                Cache::put($blockedKey, true, now()->addHours(24));
                throw ValidationException::withMessages([
                    'email' => 'Trop de tentatives, réessayez dans 24h.',
                ]);
            }

            $message = 'Les identifiants sont incorrects.';
            if ($attempts >= 5) {
                $message .= ' Il vous reste ' . (10 - $attempts) . ' tentatives.';
            }

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        if (Schema::hasTable('login_attempts')) {
            DB::table('login_attempts')->insert([
                'email' => $email,
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
                'success' => true,
                'created_at' => now(),
            ]);
        }
        $this->syncVisitorLoginAttempts($ip);
        RateLimiter::clear($key);

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function turnstileIsValid(Request $request): bool
    {
        $token = $request->input('cf-turnstile-response');
        $secret = env('TURNSTILE_SECRET_KEY');

        if (! filled($token) || ! filled($secret)) {
            return false;
        }

        try {
            return (bool) Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ])
                ->json('success', false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function syncVisitorLoginAttempts(string $ip): void
    {
        if (! Schema::hasTable('visitors_logs') || ! Schema::hasTable('login_attempts')) {
            return;
        }

        VisitorLog::where('ip', $ip)->update([
            'nombre_tentatives_login' => DB::table('login_attempts')
                ->where('ip', $ip)
                ->where('success', false)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'last_seen' => now(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $superAdminId = \App\Models\Setting::get('super_admin_id', null);
        if ($user->id == $superAdminId) {
            abort(403, 'Le super‑admin ne peut pas supprimer son propre compte.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('home')->with('success', 'Votre compte a été supprimé.');
    }
}
