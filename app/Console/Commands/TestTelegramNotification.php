<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\TelegramNotificationService;

class TestTelegramNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test-sales-notification {payment_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram sales notification for a specific payment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        
        $this->info("🧪 Testing Telegram sales notification for payment ID: {$paymentId}");
        $this->newLine();
        
        $payment = Payment::with(['user', 'subscription', 'coupon'])->find($paymentId);
        
        if (!$payment) {
            $this->error("❌ Payment not found!");
            return 1;
        }
        
        $this->info("Found payment:");
        $this->line("  ID: {$payment->id}");
        $this->line("  User: {$payment->user->first_name} {$payment->user->last_name}");
        $this->line("  Amount: " . number_format($payment->amount) . " ریال");
        $this->line("  Status: {$payment->status}");
        $this->line("  Method: {$payment->payment_method}");
        $this->line("  Subscription: " . ($payment->subscription ? "Yes (ID: {$payment->subscription->id})" : "No"));
        $this->newLine();
        
        if ($payment->status !== 'completed') {
            $this->warn("⚠️  Payment status is '{$payment->status}', not 'completed'");
            if (!$this->confirm('Continue anyway?', false)) {
                $this->warn("Test cancelled.");
                return 1;
            }
        }
        
        $this->info("📤 Sending Telegram notification...");
        
        try {
            $telegramService = app(TelegramNotificationService::class);
            $success = $telegramService->sendSalesNotification($payment, $payment->subscription);
            
            if ($success) {
                $this->info("✅ Telegram notification sent successfully!");
                $this->info("   Check your Telegram group (-1003099647147) for the message.");
            } else {
                $this->error("❌ Failed to send Telegram notification");
                $this->error("   Check the logs for more details.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error sending Telegram notification:");
            $this->error("   " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
        }
        
        return 0;
    }
}
