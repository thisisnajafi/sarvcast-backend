<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSmsService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test {phone : Phone number to send test SMS to} {--type=otp : Type of SMS (otp, payment, custom)} {--message= : Custom message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMS service functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $type = $this->option('type');
        $customMessage = $this->option('message');

        $smsService = new SmsService();

        $this->info("Testing SMS service...");
        $this->info("Phone: {$phone}");
        $this->info("Type: {$type}");

        try {
            switch ($type) {
                case 'otp':
                    $result = $smsService->sendOtp($phone, 'verification');
                    break;
                case 'payment':
                    $result = $smsService->sendPaymentNotification($phone, 50000, 'IRT');
                    break;
                case 'custom':
                    if (!$customMessage) {
                        $this->error('Custom message is required for custom type');
                        return 1;
                    }
                    $result = $smsService->sendSms($phone, $customMessage);
                    break;
                default:
                    $this->error('Invalid type. Use: otp, payment, or custom');
                    return 1;
            }

            if ($result['success']) {
                $this->info('✓ SMS sent successfully!');
                if (isset($result['message_id'])) {
                    $this->info("Message ID: {$result['message_id']}");
                }
            } else {
                $this->error('✗ SMS sending failed');
                if (isset($result['error'])) {
                    $this->error("Error: {$result['error']}");
                }
                if (isset($result['response'])) {
                    $this->error("Response: " . json_encode($result['response']));
                }
            }

        } catch (\Exception $e) {
            $this->error("Exception: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
