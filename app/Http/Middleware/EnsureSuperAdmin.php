<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $superAdminId = Setting::get('super_admin_id', null);
        if ($user->id != $superAdminId) {
            return redirect()->route('home')->with('error', 'Accès réservé au super‑admin.');
        }

        return $next($request);
    }
}
