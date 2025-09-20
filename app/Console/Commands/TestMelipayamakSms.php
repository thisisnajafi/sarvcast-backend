<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class TestMelipayamakSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test-melipayamak 
                            {phone=09136708883 : Phone number to send test SMS to}
                            {--message= : Custom message to send}
                            {--otp : Send OTP instead of regular message}
                            {--payment : Send payment notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Melipayamak SMS service with a specific phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumber = $this->argument('phone');
        $smsService = new SmsService();

        $this->info("Testing Melipayamak SMS service...");
        $this->info("Phone number: {$phoneNumber}");
        $this->info("Configuration:");
        $this->info("  - Token: " . config('services.melipayamk.token'));
        $this->info("  - Sender: " . config('services.melipayamk.sender'));
        $this->info("  - Base URL: https://rest.payamak-panel.com/api/SendSMS/SendSMS");
        $this->newLine();

        try {
            if ($this->option('otp')) {
                $this->info("Sending OTP to {$phoneNumber}...");
                $result = $smsService->sendOtp($phoneNumber, 'verification');
            } elseif ($this->option('payment')) {
                $this->info("Sending payment notification to {$phoneNumber}...");
                $result = $smsService->sendPaymentNotification($phoneNumber, 100000, 'IRT');
            } else {
                $message = $this->option('message') ?? 'تست سرویس پیامک سروکست - این یک پیام تستی است.';
                $this->info("Sending SMS to {$phoneNumber}...");
                $this->info("Message: {$message}");
                $result = $smsService->sendSms($phoneNumber, $message);
            }

            $this->newLine();
            
            if ($result['success']) {
                $this->info('✅ SMS sent successfully!');
                $this->info('Response: ' . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                if (isset($result['message_id'])) {
                    $this->info('Message ID: ' . $result['message_id']);
                }
            } else {
                $this->error('❌ SMS sending failed!');
                
                if (isset($result['error'])) {
                    $this->error('Error: ' . $result['error']);
                }
                
                if (isset($result['response'])) {
                    $this->error('Response: ' . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('SMS test command failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->newLine();
        $this->info('Test completed. Check the logs for more details.');
        
        return $result['success'] ?? false ? 0 : 1;
    }
}
