<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseNotificationService
{
    public function mirrorNewPost(Post $post): void
    {
        $url = rtrim((string) env('VITE_SUPABASE_URL'), '/');
        $anonKey = env('VITE_SUPABASE_ANON_KEY');

        if (!filled($url) || !filled($anonKey)) {
            Log::warning('Supabase notification sync skipped: VITE Supabase variables are missing.');
            return;
        }

        $client = Http::withHeaders([
            'apikey' => $anonKey,
            'Authorization' => 'Bearer ' . $anonKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ])->timeout(10);

        $postResponse = $client->post($url . '/rest/v1/posts', [[
            'id' => $post->id,
            'user_id' => $post->user_id,
            'content' => $post->content,
            'created_at' => $post->created_at?->toISOString(),
        ]]);

        if ($postResponse->failed()) {
            Log::warning('Supabase post mirror failed.', [
                'status' => $postResponse->status(),
                'response' => $postResponse->body(),
                'post_id' => $post->id,
            ]);
        }

        $recipientIds = User::query()
            ->where('id', '!=', $post->user_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!$recipientIds) {
            return;
        }

        $notifications = array_map(fn (int $userId) => [
            'user_id' => $userId,
            'type' => 'new_post',
            'title' => 'Nouveau post',
            'message' => 'Un nouvel utilisateur a publié',
            'link' => '/post/' . $post->id,
            'is_read' => false,
        ], $recipientIds);

        $notificationResponse = $client->post($url . '/rest/v1/notifications', $notifications);

        if ($notificationResponse->failed()) {
            Log::warning('Supabase notification bulk insert failed.', [
                'status' => $notificationResponse->status(),
                'response' => $notificationResponse->body(),
                'post_id' => $post->id,
            ]);
        }
    }
}