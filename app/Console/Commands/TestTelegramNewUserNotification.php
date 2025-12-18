<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegramNewUserNotification extends Command
{
    protected $signature = 'telegram:test-new-user-notification {user_id}';
    protected $description = 'Test Telegram new user notification for a specific user';

    public function handle()
    {
        $userId = $this->argument('user_id');

        $this->info("🧪 Testing Telegram new user notification for user ID: {$userId}");

        // Find the user
        $user = User::find($userId);

        if (!$user) {
            $this->error("❌ User with ID {$userId} not found");
            return Command::FAILURE;
        }

        $this->info("📋 User Details:");
        $this->info("   Name: {$user->first_name} {$user->last_name}");
        $this->info("   Phone: {$user->phone_number}");
        $this->info("   Role: {$user->role}");
        $this->info("   Created: {$user->created_at}");

        // Test Telegram service
        $telegramService = app(TelegramNotificationService::class);

        $this->info("📤 Sending Telegram new user notification...");

        try {
            $success = $telegramService->sendNewUserNotification($user);

            if ($success) {
                $this->info("✅ Telegram new user notification sent successfully!");
                $this->info("   Check your Telegram group (-1003099647147) for the message.");
                $this->info("   It should include the user's name and phone number.");
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to send Telegram new user notification");
                $this->error("   Check the logs for more details.");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error sending Telegram new user notification:");
            $this->error("   " . $e->getMessage());
            $this->error("   Check the logs for more details.");
            return Command::FAILURE;
        }
    }
}
