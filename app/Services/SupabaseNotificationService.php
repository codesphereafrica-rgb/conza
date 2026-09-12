<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseNotificationService
{
    public function notifyUsersExcept(int $authorId, array $notification): void
    {
        $userIds = User::query()
            ->where('id', '!=', $authorId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->insertForUsers($userIds, $notification);
    }

    public function notifyUser(?int $userId, array $notification): void
    {
        if ($userId) {
            $this->insertForUsers([$userId], $notification);
        }
    }

    private function insertForUsers(array $userIds, array $notification): void
    {
        $supabaseUrl = env('SUPABASE_URL') ?: env('VITE_SUPABASE_URL');
        $serviceRoleKey = env('SUPABASE_SERVICE_ROLE_KEY');

        if (!$userIds) {
            return;
        }

        if (!filled($supabaseUrl) || !filled($serviceRoleKey)) {
            Log::warning('Supabase notifications skipped: SUPABASE_URL or SUPABASE_SERVICE_ROLE_KEY is missing.');
            return;
        }

        $rows = array_map(
            fn (int $userId) => array_merge($notification, [
                'user_id' => $userId,
                'is_read' => false,
            ]),
            $userIds,
        );

        $response = Http::withHeaders([
            'apikey' => $serviceRoleKey,
            'Authorization' => 'Bearer ' . $serviceRoleKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ])->timeout(10)->post(rtrim($supabaseUrl, '/') . '/rest/v1/notifications', $rows);

        if ($response->failed()) {
            Log::warning('Supabase notification insert failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }
    }
}