<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next)
    {
        $blocked = BlockedIp::active()
            ->where('ip', $request->ip())
            ->exists();

        if ($blocked) {
            return response()->view('blocked-ip', status: 403);
        }

        return $next($request);
    }
}
