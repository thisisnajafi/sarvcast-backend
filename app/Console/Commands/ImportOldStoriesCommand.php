<?php

namespace App\Console\Commands;

use App\Services\OldStoriesImportService;
use Illuminate\Console\Command;

class ImportOldStoriesCommand extends Command
{
    protected $signature = 'stories:import-old
                            {--source= : Path to manji-stories/staging (default: auto-discover)}
                            {--dest= : Destination stories root (default: STORY_EDITOR_STORIES_PATH)}
                            {--only= : Comma-separated folder names or numeric IDs, e.g. 86,2 - gavi}
                            {--include-conflicts : Import IDs 1-7 even when they conflict with active stories}
                            {--prompts-only : Include episodes that still need scripts}
                            {--create-db : Create draft Story/Episode rows when missing}
                            {--deploy-only : Copy packages to destination without production import}
                            {--import-only : Skip deploy; import from packages already in destination}
                            {--force : Overwrite existing destination folders when deploying}
                            {--dry-run : Show import plan without writing files or DB}';

    protected $description = 'Import Phase B staging packages into the story editor (deploy + StoryProductionImportService)';

    public function __construct(
        private readonly OldStoriesImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $sourcePath = $this->importService->resolveSourcePath($this->option('source'));
            $destPath = $this->importService->resolveDestinationPath($this->option('dest'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('OLD_Stories Phase C import');
        $this->line("  Source: {$sourcePath}");
        $this->line("  Dest:   {$destPath}");

        $packages = $this->importService->listPackages($sourcePath);
        if ($packages === []) {
            $this->warn('No import_manifest.json packages found in source.');

            return self::SUCCESS;
        }

        $only = $this->parseOnlyFilter($this->option('only'));
        if ($only !== []) {
            $packages = array_values(array_filter(
                $packages,
                fn (array $pkg) => $this->matchesOnlyFilter($pkg['folder_name'], $only),
            ));
        }

        if ($packages === []) {
            $this->warn('No packages matched --only filter.');

            return self::SUCCESS;
        }

        $options = [
            'skip_conflicts' => ! $this->option('include-conflicts'),
            'prompts_only' => (bool) $this->option('prompts-only'),
            'create_db' => (bool) $this->option('create-db'),
            'deploy_only' => (bool) $this->option('deploy-only'),
            'import_only' => (bool) $this->option('import-only'),
            'force' => (bool) $this->option('force'),
            'dry_run' => (bool) $this->option('dry-run'),
        ];

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no files or database records will be changed.');
        }

        $results = [];
        $counts = [
            'imported' => 0,
            'imported_with_errors' => 0,
            'skipped_conflict' => 0,
            'deployed' => 0,
            'dry_run' => 0,
            'failed' => 0,
        ];

        foreach ($packages as $package) {
            $folder = $package['folder_name'];
            $this->newLine();
            $this->info("→ {$folder}");

            if ($options['skip_conflicts'] && $this->importService->hasIdConflict($folder, $destPath)) {
                $this->warn('  skipped (ID conflict with active story folder — use --include-conflicts to override)');
                $results[] = [
                    'folder_name' => $folder,
                    'status' => 'skipped_conflict',
                ];
                $counts['skipped_conflict']++;

                continue;
            }

            try {
                $result = $this->importService->importPackage($package, $destPath, $options);
                $results[] = $result;
                $status = $result['status'] ?? 'unknown';
                $counts[$status] = ($counts[$status] ?? 0) + 1;

                if ($status === 'dry_run') {
                    $this->line("  would deploy to: {$result['would_deploy_to']}");
                    $this->line("  episodes: {$result['episodes']}");
                } elseif ($status === 'deployed') {
                    $this->line('  deployed to destination');
                } else {
                    $importCount = count($result['imports'] ?? []);
                    $this->line("  status: {$status} ({$importCount} file imports)");
                    if (! empty($result['story_id'])) {
                        $this->line("  story_id: {$result['story_id']}");
                    }
                    foreach ($result['errors'] ?? [] as $error) {
                        $this->error("  ! {$error}");
                    }
                    foreach ($result['skipped_episodes'] ?? [] as $skipped) {
                        $this->warn("  skipped ep {$skipped['episode_number']}: {$skipped['reason']}");
                    }
                }
            } catch (\Throwable $e) {
                $this->error('  failed: ' . $e->getMessage());
                $results[] = [
                    'folder_name' => $folder,
                    'status' => 'failed',
                    'errors' => [$e->getMessage()],
                ];
                $counts['failed']++;
            }
        }

        $this->newLine();
        $this->info('Summary');
        $this->line("  packages: " . count($packages));
        $this->line("  imported: {$counts['imported']}");
        $this->line("  with errors: {$counts['imported_with_errors']}");
        $this->line("  skipped (conflict): {$counts['skipped_conflict']}");
        $this->line("  failed: {$counts['failed']}");

        if (! $this->option('dry-run')) {
            $reportPath = $this->writeReport($sourcePath, $results, $destPath, $options);
            $this->line("  report: {$reportPath}");
        }

        return ($counts['failed'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseOnlyFilter(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * @param  array<int, string>  $filters
     */
    private function matchesOnlyFilter(string $folderName, array $filters): bool
    {
        foreach ($filters as $filter) {
            if ($folderName === $filter) {
                return true;
            }

            if (ctype_digit($filter) && preg_match('/^' . preg_quote($filter, '/') . '\s*-/', $folderName)) {
                return true;
            }

            if (! ctype_digit($filter) && stripos($folderName, $filter) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @param  array<string, mixed>  $options
     */
    private function writeReport(string $sourcePath, array $results, string $destPath, array $options): string
    {
        $reportsDir = dirname($sourcePath) . DIRECTORY_SEPARATOR . 'reports';
        if (! is_dir($reportsDir)) {
            @mkdir($reportsDir, 0755, true);
        }

        $reportPath = $reportsDir . DIRECTORY_SEPARATOR . 'import_old_results.json';
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'source' => $sourcePath,
            'destination' => $destPath,
            'options' => $options,
            'results' => $results,
        ];

        file_put_contents(
            $reportPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $reportPath;
    }
}
