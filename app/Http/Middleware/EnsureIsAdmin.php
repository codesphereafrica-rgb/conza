<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     * Allow only users with role 'admin' or the super-admin id stored in settings.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $superAdminId = Setting::get('super_admin_id', null);

        if ($user->id == $superAdminId) {
            return $next($request);
        }

        if (($user->role ?? null) === 'admin') {
            return $next($request);
        }

        return redirect()->route('home')->with('error', 'Accès réservé aux administrateurs.');
    }
}
