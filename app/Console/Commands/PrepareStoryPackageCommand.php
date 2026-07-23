<?php

namespace App\Console\Commands;

use App\Services\StoryPackagePrepareService;
use App\Services\StoryPackagePreflightService;
use Illuminate\Console\Command;

class PrepareStoryPackageCommand extends Command
{
    protected $signature = 'stories:prepare-package
                            {path : Absolute/relative path to a writer story folder}
                            {--staging= : Staging root (default: <manji-stories>/staging)}
                            {--force : Overwrite existing staging package}';

    protected $description = 'Build a staging package with import_manifest.json from a writer folder';

    public function handle(
        StoryPackagePrepareService $prepareService,
        StoryPackagePreflightService $preflightService,
    ): int {
        $path = (string) $this->argument('path');

        try {
            $preflight = $preflightService->checkWriterFolder($path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($preflight['warnings'] as $warning) {
            $this->warn($warning);
        }
        if (! $preflight['ok']) {
            foreach ($preflight['issues'] as $issue) {
                $this->error($issue);
            }

            return self::FAILURE;
        }

        $staging = $this->option('staging');
        if (! is_string($staging) || $staging === '') {
            $staging = dirname($this->normalize($path)) . DIRECTORY_SEPARATOR . 'staging';
        }

        try {
            $result = $prepareService->prepareFromWriterFolder(
                $path,
                $staging,
                (bool) $this->option('force'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Prepared: ' . $result['destination']);
        $this->line('  folder: ' . $result['folder_name']);
        $this->line('  characters_json: ' . ($result['has_characters'] ? 'yes' : 'no'));
        $this->line('  episodes: ' . $result['episode_count']);

        return self::SUCCESS;
    }

    private function normalize(string $path): string
    {
        if ($path !== '' && $path[0] !== '/' && $path[0] !== '\\' && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return realpath($path) ?: $path;
    }
}
