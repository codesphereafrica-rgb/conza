<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseNotificationService
{
    public function mirrorNewTopic(Topic $topic): void
    {
        $author = $topic->user()->first();
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
            'id' => $topic->id,
            'user_id' => $topic->user_id,
            'content' => $topic->content,
            'created_at' => $topic->created_at?->toISOString(),
        ]]);

        if ($postResponse->failed()) {
            Log::warning('Supabase post mirror failed.', [
                'status' => $postResponse->status(),
                'response' => $postResponse->body(),
                'post_id' => $topic->id,
            ]);
        }

        $recipientIds = User::query()
            ->where('id', '!=', $topic->user_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!$recipientIds) {
            return;
        }

        $notifications = array_map(fn (int $userId) => [
            'user_id' => (string) $userId,
            'author_id' => (string) $topic->user_id,
            'author_name' => $author?->name ?? 'Utilisateur',
            'author_avatar' => $author?->avatar,
            'type' => 'new_post',
            'title' => 'Nouvelle publication',
            'message' => ($author?->name ?? 'Un utilisateur') . ' a fait une nouvelle publication',
            'link' => '/post/' . $topic->id,
            'is_read' => false,
        ], $recipientIds);

        $notificationResponse = $client->post($url . '/rest/v1/notifications', $notifications);

        if ($notificationResponse->failed()) {
            Log::warning('Supabase notification bulk insert failed.', [
                'status' => $notificationResponse->status(),
                'response' => $notificationResponse->body(),
                'post_id' => $topic->id,
            ]);
        }
    }

    public function notifyReplyAndCommenters(Post $post, ?Post $parentPost, Topic $topic): void
    {
        $author = $post->user()->first();
        $recipientIds = $topic->posts()
            ->where('user_id', '!=', $post->user_id)
            ->pluck('user_id')
            ->merge($parentPost?->user_id ? [$parentPost->user_id] : [])
            ->unique()
            ->values()
            ->all();

        if (!$recipientIds) {
            return;
        }

        $url = rtrim((string) env('VITE_SUPABASE_URL'), '/');
        $anonKey = env('VITE_SUPABASE_ANON_KEY');
        if (!filled($url) || !filled($anonKey)) {
            Log::warning('Supabase reply notification sync skipped: VITE Supabase variables are missing.');
            return;
        }

        $client = Http::withHeaders([
            'apikey' => $anonKey,
            'Authorization' => 'Bearer ' . $anonKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ])->timeout(10);

        $notifications = array_map(fn ($userId) => [
            'user_id' => (string) $userId,
            'author_id' => (string) $post->user_id,
            'author_name' => $author?->name ?? 'Utilisateur',
            'author_avatar' => $author?->avatar,
            'type' => $parentPost ? 'reply' : 'comment',
            'title' => $parentPost ? 'Nouvelle réponse' : 'Nouveau commentaire',
            'message' => $parentPost
                ? ($author?->name ?? 'Un utilisateur') . ' a répondu à votre commentaire'
                : ($author?->name ?? 'Un utilisateur') . ' a aussi commenté la publication',
            'link' => '/post/' . $post->id,
            'is_read' => false,
        ], $recipientIds);

        $response = $client->post($url . '/rest/v1/notifications', $notifications);
        if ($response->failed()) {
            Log::warning('Supabase reply notification insert failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
                'post_id' => $post->id,
            ]);
        }
    }
}