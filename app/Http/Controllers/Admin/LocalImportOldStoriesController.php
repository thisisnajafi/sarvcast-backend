<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Services\OldStoriesImportService;
use App\Services\StoryEditorRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class LocalImportOldStoriesController extends Controller
{
    public function __construct(
        private readonly OldStoriesImportService $importService,
        private readonly StoryEditorRepository $editorRepository,
    ) {}

    public function importOld(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:512000'],
            'folder_name' => ['nullable', 'string', 'max:191'],
            'create_db' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
            'prompts_only' => ['nullable', 'boolean'],
            'include_conflicts' => ['nullable', 'boolean'],
            'deploy_only' => ['nullable', 'boolean'],
            'import_only' => ['nullable', 'boolean'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $tempRoot = storage_path('app/local-import/' . Str::uuid()->toString());
        $zipPath = $request->file('package')->getRealPath();

        try {
            File::ensureDirectoryExists($tempRoot);
            $packagePath = $this->extractPackageZip($zipPath, $tempRoot);
            $folderName = $validated['folder_name'] ?? basename($packagePath);
            $manifestPath = $packagePath . DIRECTORY_SEPARATOR . 'import_manifest.json';

            if (! is_file($manifestPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'import_manifest.json not found in uploaded package.',
                    'error' => 'INVALID_PACKAGE',
                ], 422);
            }

            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (! is_array($manifest)) {
                return response()->json([
                    'success' => false,
                    'message' => 'import_manifest.json is invalid JSON.',
                    'error' => 'INVALID_MANIFEST',
                ], 422);
            }

            $destPath = $this->importService->resolveDestinationPath(null);
            $options = [
                'skip_conflicts' => ! $request->boolean('include_conflicts'),
                'prompts_only' => $request->boolean('prompts_only'),
                'create_db' => $request->boolean('create_db'),
                'deploy_only' => $request->boolean('deploy_only'),
                'import_only' => $request->boolean('import_only'),
                'force' => $request->boolean('force'),
                'dry_run' => $request->boolean('dry_run'),
            ];

            $package = [
                'folder_name' => $folderName,
                'path' => $packagePath,
                'manifest' => $manifest,
                'story_slug' => $this->editorRepository->storyIdFromFolder($folderName),
            ];

            if ($options['skip_conflicts'] && $this->importService->hasIdConflict($folderName, $destPath)) {
                return AdminApiResponse::success([
                    'folder_name' => $folderName,
                    'status' => 'skipped_conflict',
                ], 'Package skipped (ID conflict with active story folder).');
            }

            $result = $this->importService->importPackage($package, $destPath, $options);

            return AdminApiResponse::success($result, 'OLD_Stories package processed on server.');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'IMPORT_FAILED',
            ], 500);
        } finally {
            File::deleteDirectory($tempRoot);
        }
    }

    private function extractPackageZip(string $zipPath, string $tempRoot): string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open uploaded zip archive.');
        }

        if (! $zip->extractTo($tempRoot)) {
            $zip->close();
            throw new \RuntimeException('Could not extract uploaded zip archive.');
        }

        $zip->close();

        $entries = array_values(array_filter(scandir($tempRoot) ?: [], fn (string $e) => $e !== '.' && $e !== '..'));
        if ($entries === []) {
            throw new \RuntimeException('Uploaded zip archive is empty.');
        }

        if (count($entries) === 1 && is_dir($tempRoot . DIRECTORY_SEPARATOR . $entries[0])) {
            return $tempRoot . DIRECTORY_SEPARATOR . $entries[0];
        }

        if (is_file($tempRoot . DIRECTORY_SEPARATOR . 'import_manifest.json')) {
            return $tempRoot;
        }

        throw new \RuntimeException('Could not locate package root (import_manifest.json) in zip.');
    }
}
