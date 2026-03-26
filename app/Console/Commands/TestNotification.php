<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use Illuminate\Support\Str;

class TestNotification extends Command
{
    protected $signature = 'test:notification {action=create} {--user=test_user} {--score=85}';
    protected $description = 'Test notification system';

    public function handle()
    {
        $action = $this->argument('action');

        match($action) {
            'create' => $this->createNotification(),
            'list' => $this->listNotifications(),
            'stats' => $this->showStats(),
            'clear' => $this->clearNotifications(),
            default => $this->error('Invalid action! Use: create, list, stats, clear')
        };
    }

    private function createNotification()
    {
        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->option('user'),
            'type' => 'email',
            'channel' => 'notification',
            'payload' => [
                'title' => 'Math Assessment',
                'message' => 'Your test is complete',
                'score' => $this->option('score'),
            ],
            'status' => 'pending',
            'retry_count' => 0
        ]);

        $this->info("✓ Notification created!");
        $this->line("ID: {$notification->id}");
        $this->line("User: {$notification->user_id}");
        $this->line("Score: {$this->option('score')}%\n");
    }

    private function listNotifications()
    {
        $notifications = Notification::latest()->take(5)->get();
        
        if($notifications->isEmpty()) {
            $this->warn("No notifications found!\n");
            return;
        }

        $this->info("\nRecent Notifications:\n");
        
        foreach($notifications as $n) {
            $payload = is_array($n->payload) ? $n->payload : json_decode($n->payload, true);
            $this->line("- {$n->user_id}: {$payload['score']}% ({$n->status->value})");
        }
        $this->line("");
    }

    private function showStats()
    {
        $total = Notification::count();
        $sent = Notification::where('status', 'sent')->count();
        $failed = Notification::where('status', 'failed')->count();
        
        $this->info("\nStatistics:");
        $this->line("Total: $total");
        $this->line("Sent: $sent");
        $this->line("Failed: $failed\n");
    }

    private function clearNotifications()
    {
        if($this->confirm('Delete all notifications?')) {
            $count = Notification::count();
            Notification::truncate();
            $this->info("✓ Deleted $count notifications!\n");
        }
    }
}