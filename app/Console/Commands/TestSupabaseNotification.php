<?php

namespace App\Console\Commands;

use App\Services\SupabaseNotificationService;
use Illuminate\Console\Command;

class TestSupabaseNotification extends Command
{
    protected $signature = 'supabase:notification-test {user_id : Laravel user ID to notify}';

    protected $description = 'Insert a test notification in Supabase for one user.';

    public function handle(SupabaseNotificationService $notifications): int
    {
        $notifications->notifyUser((int) $this->argument('user_id'), [
            'type' => 'test',
            'title' => 'Notification de test',
            'message' => 'test notif',
            'data' => ['source' => 'artisan'],
        ]);

        $this->info('Test notification insert sent to Supabase. Check the application log for an API error.');

        return self::SUCCESS;
    }
}