<?php

namespace App\Jobs;

use App\Services\InAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $type;
    protected $title;
    protected $message;
    protected $options;

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
    public function __construct(int $userId, string $type, string $title, string $message, array $options = [])
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(InAppNotificationService $notificationService): void
    {
        try {
            $notification = $notificationService->createNotification(
                $this->userId,
                $this->type,
                $this->title,
                $this->message,
                $this->options
            );

            Log::info('In-app notification sent successfully', [
                'user_id' => $this->userId,
                'notification_id' => $notification->id,
                'type' => $this->type,
                'title' => $this->title
            ]);

        } catch (\Exception $e) {
            Log::error('In-app notification job failed', [
                'user_id' => $this->userId,
                'type' => $this->type,
                'title' => $this->title,
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
        Log::error('In-app notification job permanently failed', [
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'error' => $exception->getMessage()
        ]);
    }
}
