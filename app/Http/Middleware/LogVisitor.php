<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class LogVisitor
{
    public function handle(Request $request, Closure $next)
    {
        $ip = (string) $request->ip();
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent() ?? '');
        $visitor = VisitorLog::firstOrNew(['ip' => $ip]);
        $now = now();

        if ($visitor->exists && $visitor->last_seen) {
            $visitor->temps_passe += min(max($visitor->last_seen->diffInSeconds($now), 0), 300);
        }

        if (! $visitor->exists || (! $visitor->country && ! $visitor->city)) {
            $location = $this->locate($ip);
            $visitor->country = $location['country'] ?? $visitor->country;
            $visitor->city = $location['city'] ?? $visitor->city;
            $visitor->isp = $location['isp'] ?? $visitor->isp;
        }

        $visitor->user_agent = $request->userAgent();
        $visitor->device_model = $agent->device() ?: $visitor->device_model;
        $visitor->platform = $agent->platform() ?: $visitor->platform;
        $visitor->page_visitee = mb_substr($request->fullUrl(), 0, 2048);
        $visitor->user_id = Auth::id() ?: $visitor->user_id;
        $visitor->is_connected = Auth::check();
        $visitor->nombre_tentatives_login = DB::table('login_attempts')
            ->where('ip', $ip)
            ->where('success', false)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $visitor->last_seen = $now;
        $visitor->save();

        return $next($request);
    }

    private function locate(string $ip): array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }

        try {
            $response = Http::timeout(2)->get('http://ip-api.com/json/' . rawurlencode($ip), [
                'fields' => 'status,country,city,isp',
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'country' => $response->json('country'),
                    'city' => $response->json('city'),
                    'isp' => $response->json('isp'),
                ];
            }
        } catch (\Throwable) {
            // A failed geolocation request must not block the visitor.
        }

        return [];
    }
}
