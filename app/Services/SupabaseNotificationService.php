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
        if (!$userIds || !filled(env('VITE_SUPABASE_URL')) || !filled(env('VITE_SUPABASE_ANON_KEY'))) {
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
            'apikey' => env('VITE_SUPABASE_ANON_KEY'),
            'Authorization' => 'Bearer ' . env('VITE_SUPABASE_ANON_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ])->post(rtrim(env('VITE_SUPABASE_URL'), '/') . '/rest/v1/notifications', $rows);

        if ($response->failed()) {
            Log::warning('Supabase notification insert failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }
    }
}