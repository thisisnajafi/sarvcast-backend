<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Services\MediaLegacyImportService;
use App\Services\MediaInUseException;
use App\Services\MediaLibraryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaLibraryController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibrary,
        private readonly MediaLegacyImportService $legacyImport,
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:96',
            'search' => 'nullable|string|max:200',
            'folder' => ['nullable', 'string', Rule::in(config('media_library.folders', ['general']))],
            'status' => 'nullable|in:active,archived',
            'sort' => 'nullable|in:created_at,-created_at,size_bytes,-size_bytes,title,-title',
        ]);

        $perPage = min((int) ($validated['per_page'] ?? 24), 96);
        $sort = $validated['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query = MediaAsset::query()->with('uploader:id,name');

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['folder'])) {
            $query->where('folder', $validated['folder']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } else {
            $query->where('status', MediaAsset::STATUS_ACTIVE);
        }

        $query->orderBy($column, $direction);

        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(fn (MediaAsset $asset) => $asset->toApiArray());

        return AdminApiResponse::paginated($paginator);
    }

    public function store(Request $request)
    {
        $maxFiles = (int) config('media_library.max_files_per_upload', 20);

        $validated = $request->validate([
            'files' => 'required|array|min:1|max:' . $maxFiles,
            'files.*' => 'required|file|image|max:' . (int) config('media_library.max_upload_kb', 5120),
            'folder' => ['nullable', 'string', Rule::in(config('media_library.folders', ['general']))],
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            $meta = [
                'folder' => $validated['folder'] ?? 'general',
                'title' => $validated['title'] ?? null,
                'alt_text' => $validated['alt_text'] ?? null,
                'tags' => $validated['tags'] ?? null,
                'uploaded_by' => auth()->id(),
            ];

            $assets = $this->mediaLibrary->uploadMany($request->file('files', []), $meta);
            $data = array_map(fn (MediaAsset $asset) => $asset->toApiArray(), $assets);

            return AdminApiResponse::success(
                count($data) === 1 ? $data[0] : $data,
                'Media uploaded successfully',
                201,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    public function show(MediaAsset $mediaAsset)
    {
        return AdminApiResponse::success($mediaAsset->toApiArray());
    }

    public function update(Request $request, MediaAsset $mediaAsset)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'folder' => ['nullable', 'string', Rule::in(config('media_library.folders', ['general']))],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'nullable|in:active,archived',
        ]);

        $mediaAsset->update($validated);

        return AdminApiResponse::success($mediaAsset->fresh()->toApiArray(), 'Media updated successfully');
    }

    public function destroy(Request $request, MediaAsset $mediaAsset)
    {
        $hard = $request->boolean('hard');
        $force = $request->boolean('force');

        try {
            $this->mediaLibrary->delete($mediaAsset, $hard, $force);
        } catch (MediaInUseException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'IN_USE',
                'data' => ['usages' => $e->usages],
            ], 422);
        }

        return AdminApiResponse::okMessage($hard ? 'Media deleted permanently' : 'Media archived');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,archive,restore',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:media_assets,id',
            'hard' => 'nullable|boolean',
            'force' => 'nullable|boolean',
        ]);

        $assets = MediaAsset::whereIn('id', $validated['ids'])->get();
        $hard = (bool) ($validated['hard'] ?? false);
        $count = 0;

        foreach ($assets as $asset) {
            try {
                match ($validated['action']) {
                    'delete' => $this->mediaLibrary->delete($asset, $hard, (bool) ($validated['force'] ?? false)),
                    'archive' => $this->mediaLibrary->archive($asset),
                    'restore' => $asset->update(['status' => MediaAsset::STATUS_ACTIVE]),
                };
                $count++;
            } catch (MediaInUseException) {
                continue;
            }
        }

        return AdminApiResponse::success(['processed' => $count], 'Bulk action completed');
    }

    public function statistics()
    {
        $active = MediaAsset::where('status', MediaAsset::STATUS_ACTIVE)->count();
        $archived = MediaAsset::where('status', MediaAsset::STATUS_ARCHIVED)->count();
        $totalBytes = (int) MediaAsset::where('status', MediaAsset::STATUS_ACTIVE)->sum('size_bytes');

        $usedAssetIds = MediaUsage::query()->distinct()->pluck('media_asset_id');
        $inUse = MediaAsset::query()
            ->where('status', MediaAsset::STATUS_ACTIVE)
            ->whereIn('id', $usedAssetIds)
            ->count();
        $legacy = MediaAsset::query()
            ->where('status', MediaAsset::STATUS_ACTIVE)
            ->where('disk', MediaLegacyImportService::LEGACY_DISK)
            ->count();
        $recentWeek = MediaAsset::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $byFolder = MediaAsset::query()
            ->where('status', MediaAsset::STATUS_ACTIVE)
            ->selectRaw('folder, COUNT(*) as count, COALESCE(SUM(size_bytes), 0) as size_bytes')
            ->groupBy('folder')
            ->orderByDesc('count')
            ->get()
            ->map(static fn ($row) => [
                'folder' => $row->folder,
                'count' => (int) $row->count,
                'size_mb' => round(((int) $row->size_bytes) / 1024 / 1024, 2),
            ])
            ->values()
            ->all();

        $usageByType = MediaUsage::query()
            ->selectRaw('usable_type, COUNT(*) as count')
            ->groupBy('usable_type')
            ->orderByDesc('count')
            ->get()
            ->map(static fn ($row) => [
                'type' => class_basename($row->usable_type),
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        return AdminApiResponse::success([
            'total' => $active + $archived,
            'active' => $active,
            'archived' => $archived,
            'total_size_bytes' => $totalBytes,
            'total_size_mb' => round($totalBytes / 1024 / 1024, 2),
            'in_use' => $inUse,
            'unused' => max(0, $active - $inUse),
            'legacy_imported' => $legacy,
            'uploads_last_7_days' => $recentWeek,
            'by_folder' => $byFolder,
            'usage_by_type' => $usageByType,
        ]);
    }

    public function legacyImportPreview()
    {
        return AdminApiResponse::success($this->legacyImport->preview());
    }

    public function legacyImport(Request $request)
    {
        $validated = $request->validate([
            'dry_run' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $result = $this->legacyImport->import(
            (bool) ($validated['dry_run'] ?? false),
            (int) ($validated['limit'] ?? 100),
        );

        return AdminApiResponse::success($result, $result['dry_run'] ? 'Legacy import preview completed' : 'Legacy import completed');
    }
}
