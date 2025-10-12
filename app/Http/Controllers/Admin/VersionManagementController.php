<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Services\VersionCheckService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VersionManagementController extends Controller
{
    protected VersionCheckService $versionCheckService;

    public function __construct(VersionCheckService $versionCheckService)
    {
        $this->versionCheckService = $versionCheckService;
    }

    /**
     * Display a listing of app versions.
     */
    public function index(Request $request): View
    {
        $query = AppVersion::query();

        // Apply filters
        if ($request->filled('platform')) {
            $query->where('platform', $request->input('platform'));
        }

        if ($request->filled('update_type')) {
            $query->where('update_type', $request->input('update_type'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->input('status') === 'latest') {
                $query->where('is_latest', true);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $versions = $query->paginate(20);

        // Get statistics
        $stats = $this->versionCheckService->getVersionStatistics();

        return view('admin.versions.index', compact('versions', 'stats'));
    }

    /**
     * Show the form for creating a new app version.
     */
    public function create(): View
    {
        return view('admin.versions.create');
    }

    /**
     * Store a newly created app version.
     */
    public function store(Request $request): RedirectResponse
    {
        // Debug: Log the request data
        Log::info('Version creation request data', $request->all());

        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:20|unique:app_versions,version',
            'build_number' => 'nullable|string|max:50',
            'platform' => 'required|string|in:android,ios,web,all',
            'update_type' => 'required|string|in:optional,forced,maintenance',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'changelog' => 'nullable|string',
            'update_notes' => 'nullable|string',
            'download_url' => 'nullable|url',
            'minimum_os_version' => 'nullable|string|max:20',
            'compatibility' => 'nullable|array',
            'is_active' => 'boolean',
            'is_latest' => 'boolean',
            'release_date' => 'nullable|date',
            'force_update_date' => 'nullable|date|after:release_date',
            'priority' => 'integer|min:0|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Debug: Log what we're trying to create
            $dataToCreate = $request->all();
            Log::info('Data being sent to AppVersion::create', $dataToCreate);

            $version = AppVersion::create($dataToCreate);

            // If this is set as latest, update other versions
            if ($request->boolean('is_latest')) {
                $this->versionCheckService->setAsLatest($version);
            }

            Log::info('App version created', [
                'version' => $version->version,
                'platform' => $version->platform,
                'update_type' => $version->update_type,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.versions.index')
                ->with('success', 'نسخه جدید با موفقیت ایجاد شد');

        } catch (\Exception $e) {
            Log::error('Failed to create app version', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return redirect()->back()
                ->with('error', 'خطا در ایجاد نسخه: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified app version.
     */
    public function show(AppVersion $version): View
    {
        return view('admin.versions.show', compact('version'));
    }

    /**
     * Show the form for editing the specified app version.
     */
    public function edit(AppVersion $version): View
    {
        return view('admin.versions.edit', compact('version'));
    }

    /**
     * Update the specified app version.
     */
    public function update(Request $request, AppVersion $version): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:20|unique:app_versions,version,' . $version->id,
            'build_number' => 'nullable|string|max:50',
            'platform' => 'required|string|in:android,ios,web,all',
            'update_type' => 'required|string|in:optional,forced,maintenance',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'changelog' => 'nullable|string',
            'update_notes' => 'nullable|string',
            'download_url' => 'nullable|url',
            'minimum_os_version' => 'nullable|string|max:20',
            'compatibility' => 'nullable|array',
            'is_active' => 'boolean',
            'is_latest' => 'boolean',
            'release_date' => 'nullable|date',
            'force_update_date' => 'nullable|date|after:release_date',
            'priority' => 'integer|min:0|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $oldVersion = $version->version;
            $oldPlatform = $version->platform;

            $version->update($request->all());

            // If this is set as latest, update other versions
            if ($request->boolean('is_latest')) {
                $this->versionCheckService->setAsLatest($version);
            }

            Log::info('App version updated', [
                'version_id' => $version->id,
                'old_version' => $oldVersion,
                'new_version' => $version->version,
                'platform' => $version->platform,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.versions.index')
                ->with('success', 'نسخه با موفقیت به‌روزرسانی شد');

        } catch (\Exception $e) {
            Log::error('Failed to update app version', [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return redirect()->back()
                ->with('error', 'خطا در به‌روزرسانی نسخه: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified app version.
     */
    public function destroy(AppVersion $version): RedirectResponse
    {
        try {
            $versionInfo = [
                'version' => $version->version,
                'platform' => $version->platform,
                'update_type' => $version->update_type,
            ];

            $version->delete();

            Log::info('App version deleted', [
                'version_info' => $versionInfo,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.versions.index')
                ->with('success', 'نسخه با موفقیت حذف شد');

        } catch (\Exception $e) {
            Log::error('Failed to delete app version', [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'خطا در حذف نسخه: ' . $e->getMessage());
        }
    }

    /**
     * Toggle version active status.
     */
    public function toggleActive(AppVersion $version): RedirectResponse
    {
        try {
            if ($version->is_active) {
                $this->versionCheckService->deactivateVersion($version);
                $message = 'نسخه غیرفعال شد';
            } else {
                $this->versionCheckService->activateVersion($version);
                $message = 'نسخه فعال شد';
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to toggle version active status', [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'خطا در تغییر وضعیت نسخه: ' . $e->getMessage());
        }
    }

    /**
     * Set version as latest.
     */
    public function setAsLatest(AppVersion $version): RedirectResponse
    {
        try {
            $this->versionCheckService->setAsLatest($version);

            Log::info('Version set as latest', [
                'version' => $version->version,
                'platform' => $version->platform,
                'set_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'نسخه به عنوان آخرین نسخه تنظیم شد');

        } catch (\Exception $e) {
            Log::error('Failed to set version as latest', [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'خطا در تنظیم نسخه: ' . $e->getMessage());
        }
    }

    /**
     * Get version statistics for dashboard.
     */
    public function statistics(): View
    {
        $stats = $this->versionCheckService->getVersionStatistics();

        // Get recent versions
        $recentVersions = AppVersion::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get platform distribution
        $platformStats = AppVersion::active()
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->get();

        // Get update type distribution
        $updateTypeStats = AppVersion::active()
            ->selectRaw('update_type, COUNT(*) as count')
            ->groupBy('update_type')
            ->get();

        return view('admin.versions.statistics', compact(
            'stats',
            'recentVersions',
            'platformStats',
            'updateTypeStats'
        ));
    }

    /**
     * Clear version cache.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            $this->versionCheckService->clearCache();

            Log::info('Version cache cleared', [
                'cleared_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'کش نسخه‌ها پاک شد');

        } catch (\Exception $e) {
            Log::error('Failed to clear version cache', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'خطا در پاک کردن کش: ' . $e->getMessage());
        }
    }
}
