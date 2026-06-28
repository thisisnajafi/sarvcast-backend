<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use ZipArchive;

class OldStoriesRemoteImportClient
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function uploadPackage(string $packagePath, string $folderName, array $options = []): array
    {
        $baseUrl = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
        $token = (string) env('LOCAL_IMPORT_API_TOKEN');

        if ($baseUrl === '' || $token === '') {
            throw new \RuntimeException(
                'Set LOCAL_IMPORT_API_BASE_URL and LOCAL_IMPORT_API_TOKEN in manji-laravel/.env (local machine only).'
            );
        }

        $zipPath = $this->zipDirectory($packagePath);
        try {
            $response = Http::withToken($token)
                ->timeout(600)
                ->attach(
                    'package',
                    fopen($zipPath, 'r'),
                    $folderName . '.zip',
                )
                ->post("{$baseUrl}/local-import/stories/import-old", [
                    'folder_name' => $folderName,
                    'create_db' => $options['create_db'] ?? false ? '1' : '0',
                    'force' => $options['force'] ?? false ? '1' : '0',
                    'prompts_only' => $options['prompts_only'] ?? false ? '1' : '0',
                    'include_conflicts' => ! ($options['skip_conflicts'] ?? true) ? '1' : '0',
                    'deploy_only' => $options['deploy_only'] ?? false ? '1' : '0',
                    'import_only' => $options['import_only'] ?? false ? '1' : '0',
                    'dry_run' => $options['dry_run'] ?? false ? '1' : '0',
                ]);

            if (! $response->successful()) {
                $message = $response->json('message') ?? $response->body();
                throw new \RuntimeException("Remote import failed (HTTP {$response->status()}): {$message}");
            }

            $payload = $response->json();
            if (! is_array($payload) || ! ($payload['success'] ?? false)) {
                throw new \RuntimeException('Remote import returned an unsuccessful response.');
            }

            return is_array($payload['data'] ?? null) ? $payload['data'] : [];
        } finally {
            @unlink($zipPath);
        }
    }

    public function verifyAccess(): array
    {
        $baseUrl = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
        $token = (string) env('LOCAL_IMPORT_API_TOKEN');

        $response = Http::withToken($token)
            ->timeout(30)
            ->get("{$baseUrl}/local-import/verify");

        if (! $response->successful()) {
            throw new \RuntimeException('Local import API verify failed: ' . ($response->json('message') ?? $response->body()));
        }

        return $response->json('data') ?? [];
    }

    private function zipDirectory(string $directory): string
    {
        if (! is_dir($directory)) {
            throw new \RuntimeException("Package directory not found: {$directory}");
        }

        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'old-story-' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create temporary zip archive.');
        }

        $directory = rtrim($directory, '\\/');
        $baseLength = strlen($directory) + 1;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $localPath = $item->getPathname();
            $relative = substr($localPath, $baseLength);
            $relative = str_replace('\\', '/', $relative);

            if ($item->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($localPath, $relative);
            }
        }

        $zip->close();

        return $zipPath;
    }
}
