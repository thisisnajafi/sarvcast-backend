<?php

namespace App\Jobs;

use App\Services\SmsService;
use App\Support\NewUserNotificationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;
    protected $provider;
    protected $templateKey;
    protected $variables;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $message = null, string $provider = null, string $templateKey = null, array $variables = [])
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->provider = $provider;
        $this->templateKey = $templateKey;
        $this->variables = $variables;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        $isWelcome = $this->templateKey === 'welcome';
        $queueConnection = $this->connection ?: (string) config('queue.default');
        if ($isWelcome) {
            NewUserNotificationLogger::debug('welcome_sms_job_started', [
                'source' => self::class,
                'template_key' => 'welcome',
                'channel' => 'sms',
                'queued' => true,
                'queue_connection' => $queueConnection,
                'queue' => $this->queue,
                'attempt' => $this->attempts(),
            ]);
        }
        try {
            if ($this->templateKey) {
                $result = $smsService->sendTemplateSms(
                    $this->phoneNumber,
                    $this->templateKey,
                    $this->variables,
                    $this->provider
                );
            } else {
                $result = $smsService->sendSms(
                    $this->phoneNumber,
                    $this->message,
                    $this->provider
                );
            }

            if ($isWelcome) {
                $succeeded = (bool) ($result['success'] ?? false);
                NewUserNotificationLogger::write($succeeded ? 'debug' : 'error', $succeeded ? 'welcome_sms_job_succeeded' : 'welcome_sms_job_failed', [
                    'source' => self::class,
                    'template_key' => 'welcome',
                    'channel' => 'sms',
                    'queue_connection' => $queueConnection,
                    'success' => $succeeded,
                    'provider' => $result['data']['provider'] ?? $this->provider,
                ]);
            }

            if (!$result['success']) {
                Log::warning('SMS notification failed', [
                    'phone_number' => $this->phoneNumber,
                    'template_key' => $this->templateKey,
                    'message' => $this->message,
                    'result' => $result
                ]);

                // Fail the job if it's a critical error
                if (in_array($result['error_code'] ?? '', ['INVALID_PHONE', 'NO_ACTIVE_PROVIDER'])) {
                    $this->fail();
                }
            } else {
                Log::info('SMS notification sent successfully', [
                    'phone_number' => $this->phoneNumber,
                    'template_key' => $this->templateKey,
                    'provider' => $result['data']['provider'] ?? null
                ]);
            }

        } catch (\Exception $e) {
            if ($isWelcome) {
                NewUserNotificationLogger::error('welcome_sms_job_failed', array_merge([
                    'source' => self::class,
                    'template_key' => 'welcome',
                    'channel' => 'sms',
                    'queue_connection' => $queueConnection,
                ], NewUserNotificationLogger::exceptionContext($e)));
            }
            Log::error('SMS notification job failed', [
                'phone_number' => $this->phoneNumber,
                'template_key' => $this->templateKey,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->templateKey === 'welcome') {
            NewUserNotificationLogger::error('welcome_sms_job_permanently_failed', array_merge([
                'source' => self::class,
                'template_key' => 'welcome',
                'channel' => 'sms',
                'queue_connection' => $this->connection ?: (string) config('queue.default'),
            ], NewUserNotificationLogger::exceptionContext($exception)));
        }
        Log::error('SMS notification job permanently failed', [
            'phone_number' => $this->phoneNumber,
            'template_key' => $this->templateKey,
            'error' => $exception->getMessage()
        ]);
    }
}
