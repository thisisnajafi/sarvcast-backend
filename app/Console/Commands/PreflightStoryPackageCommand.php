<?php

namespace App\Console\Commands;

use App\Services\StoryPackagePreflightService;
use Illuminate\Console\Command;

class PreflightStoryPackageCommand extends Command
{
    protected $signature = 'stories:preflight-package
                            {path : Absolute/relative path to a writer story folder}
                            {--json : Print machine-readable JSON}';

    protected $description = 'Validate writer story folder before prepare/upload (files + speakers)';

    public function handle(StoryPackagePreflightService $preflightService): int
    {
        $path = (string) $this->argument('path');

        try {
            $result = $preflightService->checkWriterFolder($path);
        } catch (\Throwable $e) {
            if ($this->option('json')) {
                $this->line(json_encode([
                    'ok' => false,
                    'issues' => [$e->getMessage()],
                    'warnings' => [],
                ], JSON_UNESCAPED_UNICODE));
            } else {
                $this->error($e->getMessage());
            }

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $result['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Preflight: ' . $result['folder']);
        foreach ($result['warnings'] as $warning) {
            $this->warn('  warning: ' . $warning);
        }
        foreach ($result['issues'] as $issue) {
            $this->error('  issue: ' . $issue);
        }

        if ($result['ok']) {
            $this->info('OK');

            return self::SUCCESS;
        }

        $this->error('FAILED');

        return self::FAILURE;
    }
}
