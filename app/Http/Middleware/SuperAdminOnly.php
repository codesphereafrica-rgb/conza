<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $isSuperAdmin = $user && (
            $user->role === 'super_admin'
            || (bool) $user->is_super_admin
            || (string) $user->id === (string) Setting::get('super_admin_id')
        );

        abort_unless($isSuperAdmin, 403);

        return $next($request);
    }
}
