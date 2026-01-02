<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test {phone : Phone number to send test notification to} {--title= : Notification title} {--body= : Notification body}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test push notification functionality by sending a notification to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $title = $this->option('title') ?: 'تست اعلان';
        $body = $this->option('body') ?: 'این یک اعلان تستی است';

        $this->info("🔔 Testing Push Notification");
        $this->info("============================");
        $this->newLine();

        // Find user by phone number
        $this->info("🔍 Looking for user with phone: {$phone}");
        $user = User::findByPhoneNumber($phone);

        if (!$user) {
            $this->error("❌ User not found with phone number: {$phone}");
            return Command::FAILURE;
        }

        $this->info("✅ User found:");
        $this->line("   ID: {$user->id}");
        $this->line("   Name: {$user->first_name} {$user->last_name}");
        $this->line("   Phone: {$user->phone_number}");
        $this->line("   Status: {$user->status}");
        $this->newLine();

        // Check if user has FCM tokens
        $fcmTokens = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($fcmTokens)) {
            $this->warn("⚠️  User has no registered FCM tokens");
            $this->warn("   The user needs to open the Flutter app to register their device");
            return Command::FAILURE;
        }

        $this->info("📱 Found " . count($fcmTokens) . " registered device(s)");
        $this->newLine();

        // Send notification
        $this->info("📤 Sending push notification...");
        $this->line("   Title: {$title}");
        $this->line("   Body: {$body}");
        $this->newLine();

        try {
            $notificationService = app(NotificationService::class);
            $result = $notificationService->sendPushNotification($user, $title, $body, [
                'type' => 'test',
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($result) {
                $this->info("✅ Push notification sent successfully!");
                $this->info("   Check the user's device for the notification");
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to send push notification");
                $this->error("   Check Laravel logs for details");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("   Stack trace:");
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

