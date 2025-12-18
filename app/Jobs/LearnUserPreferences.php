<?php

namespace App\Jobs;

use App\Services\ContentPersonalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LearnUserPreferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(ContentPersonalizationService $personalizationService): void
    {
        try {
            Log::info("Starting preference learning for user {$this->userId}");
            
            $personalizationService->learnUserPreferences($this->userId);
            
            Log::info("Preference learning completed for user {$this->userId}");
            
        } catch (\Exception $e) {
            Log::error("Error learning preferences for user {$this->userId}: " . $e->getMessage());
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Preference learning job failed for user {$this->userId}: " . $exception->getMessage());
    }
}