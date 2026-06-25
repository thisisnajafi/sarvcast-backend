<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        ActivityLog::query()->create($this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to persist activity log', [
            'exception' => $exception->getMessage(),
            'action' => $this->payload['action'] ?? null,
            'channel' => $this->payload['channel'] ?? null,
        ]);
    }
}
