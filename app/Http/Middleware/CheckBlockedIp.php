<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('up')) {
            return $next($request);
        }

        try {
            if (! Schema::hasTable('blocked_ips')) {
                return $next($request);
            }

            $blocked = BlockedIp::active()
                ->where('ip', $request->ip())
                ->exists();

            if ($blocked) {
                return response()->view('blocked-ip', status: 403);
            }
        } catch (\Throwable) {
            // Do not take the entire site down if the security table is unavailable.
        }

        return $next($request);
    }
}
