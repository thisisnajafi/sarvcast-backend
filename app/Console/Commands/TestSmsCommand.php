<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test {phone?} {--message=} {--method=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMS sending functionality';

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone') ?: '09123456789';
        $message = $this->option('message') ?: 'Test SMS from SarvCast';
        $method = $this->option('method') ?: 'otp';

        $this->info("Testing SMS sending...");
        $this->info("Phone: {$phone}");
        $this->info("Message: {$message}");
        $this->info("Method: {$method}");

        $startTime = microtime(true);

        try {
            if ($method === 'otp') {
                $result = $this->smsService->sendOtp($phone, 'test');
            } elseif ($method === 'regular') {
                $result = $this->smsService->sendSms($phone, $message);
            } else {
                $this->error("Invalid method. Use 'otp' or 'regular'");
                return;
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("Duration: {$duration}s");

            if ($result['success']) {
                $this->info("✅ SMS sent successfully!");
                $this->info("Message ID: " . ($result['message_id'] ?? 'N/A'));
                $this->info("Method used: " . ($result['method'] ?? 'unknown'));
            } else {
                $this->error("❌ SMS sending failed!");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
            }

            $this->info("Response: " . json_encode($result, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->error("❌ Exception occurred!");
            $this->error("Duration: {$duration}s");
            $this->error("Error: " . $e->getMessage());
            $this->error("Type: " . get_class($e));
        }
    }
}
